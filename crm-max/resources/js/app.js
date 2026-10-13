import "./bootstrap";
import "../css/app.css";
import "../css/leadmax.css";
import "../css/leadmax-light.css";
import "../css/leadmax-reference.css";
import "./leadmax";
import "./leadmax-leads";
import "./leadmax-drive";
import "./leadmax-people";
import "../css/leadmax-leads.css";
import "../css/leadmax-details.css";
import "../css/leadmax-rtl.css";
import "../css/leadmax-leads-light.css";
import "../css/leadmax-comms.css";
import "../css/leadmax-settings.css";
import "../css/leadmax-finance.css";
import "../css/leadmax-lead-tabs.css";
import "../css/leadmax-upload.css";
import "../css/leadmax-splitter.css";
import "../css/leadmax-reports.css";
import "../css/leadmax-tools.css";
import "../css/leadmax-auth.css";
import "@protonemedia/laravel-splade/dist/style.css";
import "./adminTranslate";

import 'flowbite';

import "@protonemedia/laravel-splade/dist/jodit.css";


const VueApexCharts = defineAsyncComponent(() => import("vue3-apexcharts"));

import { Toaster } from 'vue-sonner'



import { createApp, defineAsyncComponent } from "vue/dist/vue.esm-bundler.js";
// Page-specific components (charts, trading widgets, old admin screens) load on first use, so
// every page no longer downloads all of them up front.
import { renderSpladeApp, SpladePlugin } from "@protonemedia/laravel-splade";

import toggleDark from "./components/toggleDark.vue";
const TradingView = defineAsyncComponent(() => import("./components/TradeView.vue"));

import SideBar from "./components/SideBar.vue";
// import GoogleAddress from "./components/GoogleAddress.vue";
const ImageUpload = defineAsyncComponent(() => import("./components/ImageUpload.vue"));
const AddressAutoComplete = defineAsyncComponent(() => import("./components/AddressAutoComplete.vue"));
const VariationManager = defineAsyncComponent(() => import("./components/VariationManager.vue"));
const MobileDashboard = defineAsyncComponent(() => import("./components/MobileDashboard.vue"));


const el = document.getElementById("app");

const Base = defineAsyncComponent(() => import("./components/Base.vue"));
const Call = defineAsyncComponent(() => import("./components/Call.vue"));
const AssetList = defineAsyncComponent(() => import("./components/AssetList.vue"));
const OpenTrades = defineAsyncComponent(() => import("./components/OpenTrades.vue"));
const TradingPanel = defineAsyncComponent(() => import("./components/TradingPanel.vue"));
const Timer = defineAsyncComponent(() => import("./components/Timer.vue"));
const CustomerInfo = defineAsyncComponent(() => import("./components/CustomerInfo.vue"));
const Balance = defineAsyncComponent(() => import("./components/Balance.vue"));
const TradeNav = defineAsyncComponent(() => import("./components/TradeNav.vue"));
const AdminOpenTrades = defineAsyncComponent(() => import("./components/AdminOpenTrades.vue"));
const AdminClosedTrades = defineAsyncComponent(() => import("./components/AdminClosedTrades.vue"));
const AdminOpenTrade = defineAsyncComponent(() => import("./components/AdminOpenTrade.vue"));
const OpenPnlBalance = defineAsyncComponent(() => import("./components/OpenPnlBalance.vue"));
const MarketActivity = defineAsyncComponent(() => import("./components/MarketActivity.vue"));
const MiniChart = defineAsyncComponent(() => import("./components/MiniChart.vue"));
const MobileNav = defineAsyncComponent(() => import("./components/MobileNav.vue"));
const TradingFooter = defineAsyncComponent(() => import("./components/TradingFooter.vue"));
const OrderBook = defineAsyncComponent(() => import("./components/OrderBook.vue"));
const InvestmentPlans = defineAsyncComponent(() => import("./components/InvestmentPlans.vue"));
const ECNPlans = defineAsyncComponent(() => import("./components/ECNPlans.vue"));
const TransactionsTable = defineAsyncComponent(() => import("./components/TransactionsTable.vue"));
const InvestmentTable = defineAsyncComponent(() => import("./components/InvestmentTable.vue"));
const ECNTable = defineAsyncComponent(() => import("./components/ECNTable.vue"));
const PortfolioToggle = defineAsyncComponent(() => import("./components/PortfolioToggle.vue"));
const MarketPulse = defineAsyncComponent(() => import("./components/MarketPulse.vue"));
const SimpleMarketTest = defineAsyncComponent(() => import("./components/SimpleMarketTest.vue"));
const AdminChat = defineAsyncComponent(() => import("./components/AdminChat.vue"));
const UserProfileCard = defineAsyncComponent(() => import("./components/UserProfileCard.vue"));
const UserToggleButtons = defineAsyncComponent(() => import("./components/UserToggleButtons.vue"));
const UpdateSelect = defineAsyncComponent(() => import("./components/UpdateSelect.vue"));
const PasswordField = defineAsyncComponent(() => import("./components/PasswordField.vue"));
const MiniCustomerMessages = defineAsyncComponent(() => import("./components/MiniCustomerMessages.vue"));
const AdminGrantChallengeWizard = defineAsyncComponent(() => import("./components/AdminGrantChallengeWizard.vue"));
const ChallengeOpenTrade = defineAsyncComponent(() => import("./components/ChallengeOpenTrade.vue"));

const app = createApp({
    render: renderSpladeApp({ el })
});

app.use(SpladePlugin, {
    "max_keep_alive": 10,
    "transform_anchors": false,
    "progress_bar": true,
    "components" : {
        apexchart: VueApexCharts,
        Base,
        call_btn : Call,
        asset_list : AssetList,
        trade_view : TradingView,
        trading_panel : TradingPanel,
        open_trades : OpenTrades,
        ecn_plans : ECNPlans,
        transactions_table : TransactionsTable,
        investment_table : InvestmentTable,
        ecn_table : ECNTable,
        portfolio_toggle : PortfolioToggle,
        timer : Timer,
        ImageUpload,
        InvestmentPlans,
        toggleDark,
        // GoogleAddress,
        SideBar,
        MarketPulse,
        SimpleMarketTest,
        CustomerInfo,
        address_auto_complete : AddressAutoComplete,
        VariationManager,
        Toaster,
        Balance,
        AdminOpenTrades,
        AdminClosedTrades,
        OrderBook,
        AdminOpenTrade,
        OpenPnlBalance,
        TradeNav,
        MarketActivity,
        MiniChart,
        mobile_nav:MobileNav,
        trading_footer:TradingFooter,
        AdminChat,
        UserProfileCard,
        UserToggleButtons,
        UpdateSelect,
        PasswordField,
        MiniCustomerMessages,
        AdminGrantChallengeWizard,
        ChallengeOpenTrade
    }
});

app.mount(el);
