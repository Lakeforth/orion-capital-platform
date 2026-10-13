import { io } from 'socket.io-client'

/**
 * The back office's half of "one live price, one live PnL".
 *
 * The trade room prices open positions off a socket tick stream from the
 * asset-data service. The back office used to price the same positions off
 * Asset.rate, refreshed by a once-a-minute scheduled job and read by a
 * ten-second poll -- so the two surfaces could legitimately be over a minute
 * apart on the same open trade, which is exactly the disagreement QA
 * reported. This subscribes the back office to that same stream.
 *
 * Deliberately not a second PnL formula. The server still computes the
 * authoritative closing price for each trade, including whatever spread
 * applies to that specific customer; all this does is carry that same
 * relationship forward onto newer ticks between polls. See livePnlFor().
 */

// Mirrors the backend PriceValidator: a tick that isn't a usable price never
// reaches a PnL figure, on either surface. Ordering is enforced per asset off
// the provider's own timestamp, because arrival order is not send order.
const lastTickAt = new Map()

function isUsableTick(update) {
    const price = Number(update?.price)

    if (!Number.isFinite(price) || price <= 0) {
        return false
    }

    if (!update.lastUpdated) {
        return true
    }

    const tickAt = Date.parse(update.lastUpdated)

    if (Number.isNaN(tickAt)) {
        return true
    }

    const previous = lastTickAt.get(update.id)

    if (previous !== undefined && tickAt <= previous) {
        return false
    }

    lastTickAt.set(update.id, tickAt)

    return true
}

// How old a still-connected live price is allowed to get before it's no
// longer trusted over the poll's own number -- see isPriceEntryFresh().
export const PRICE_STALE_AFTER_MS = 20000

/**
 * Opens the shared price stream and hands back a map of coin_id -> {price,
 * receivedAt} on every batch. Returns a disconnect function.
 *
 * Staleness is judged by receivedAt at read time (isPriceEntryFresh), not by
 * socket connection events. An earlier version of this tried to clear
 * livePrices on 'disconnect'/'connect_error' instead -- wrong on two counts.
 * First, 'reconnect_failed' (the actual "never coming back" signal) is a
 * Manager event in socket.io v4, emitted on the Manager instance, not the
 * Socket -- socket.on('reconnect_failed', ...) here silently never fires.
 * Second, and worse, connect_error fires on *every* failed reconnection
 * attempt during a normal retry sequence, not just a final one -- so a
 * connection blip that recovers in a second or two was blanking livePrices
 * and letting it refill right after, a visible jump on every reconnect
 * instead of the one it was meant to fix. A receivedAt check has none of
 * that: a two-second blip is invisible (last good price is still fresh when
 * read), and a feed that's actually dead ages out on its own without any
 * event wiring to get wrong.
 */
export function connectPriceFeed({ url, apiKey, onPrices }) {
    const socket = io(url, {
        auth: { apiKey },
        transports: ['websocket'],
        reconnectionAttempts: 5,
        timeout: 10000,
    })

    const handle = (payload) => {
        const updates = Array.isArray(payload) ? payload : [payload]
        const prices = {}
        const receivedAt = Date.now()

        updates.forEach((update) => {
            if (update && isUsableTick(update)) {
                prices[update.id] = { price: Number(update.price), receivedAt }
            }
        })

        if (Object.keys(prices).length > 0) {
            onPrices(prices)
        }
    }

    socket.on('connect', () => socket.emit('subscribe:all'))
    socket.on('data:all', (response) => {
        if (response?.success && response.data?.length) {
            handle(response.data)
        }
    })
    socket.on('data:update', handle)

    return () => {
        socket.removeAllListeners()
        socket.close()
    }
}

export function isPriceEntryFresh(entry) {
    return !!entry && Date.now() - entry.receivedAt <= PRICE_STALE_AFTER_MS
}

/**
 * Re-prices one server-returned trade against a newer tick.
 *
 * The server's closing_price already has this customer's side of the spread
 * baked into it (buy positions close on the raw rate, sell positions close on
 * rate + spread, and the spread itself depends on that customer's
 * spread_multiplier). None of that is knowable from the back office session,
 * which is authenticated as an admin -- so instead of trying to recompute it,
 * take the offset the server produced and carry it onto the new rate. The
 * result is the same number the server would return if it were asked again
 * right now, and it re-baselines on the next poll.
 */
export function livePnlFor(trade, liveRate) {
    const baselineRate = Number(trade?.asset?.rate)
    const baselineClosing = Number(trade?.closing_price)
    const openingPrice = Number(trade?.opening_price)
    const volume = Number(trade?.volume)
    const quoteRate = Number(trade?.asset?.quote_rate ?? 1)

    if (
        !Number.isFinite(liveRate) ||
        liveRate <= 0 ||
        !Number.isFinite(baselineRate) ||
        baselineRate <= 0 ||
        !Number.isFinite(baselineClosing) ||
        !Number.isFinite(openingPrice) ||
        !Number.isFinite(volume) ||
        !Number.isFinite(quoteRate) ||
        quoteRate <= 0
    ) {
        return null
    }

    const closingPrice = liveRate + (baselineClosing - baselineRate)

    const priceDifference =
        trade.trade_type === 'buy' ? closingPrice - openingPrice : openingPrice - closingPrice

    return {
        closing_price: closingPrice,
        // Price difference x volume is in the instrument's quote currency.
        // Match TradeService by converting it to the account currency before
        // replacing the server-returned PnL between polls.
        profit: priceDifference * volume * quoteRate,
    }
}
