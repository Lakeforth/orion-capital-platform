import { useState } from "react";
import { motion } from "framer-motion";
import { Link } from "react-router-dom";

interface PartnerLogo {
  name: string;
  logo: string;
  url: string;
}

export default function CryptoPartners() {
  const [hoveredLogo, setHoveredLogo] = useState<string | null>(null);

  const partners: PartnerLogo[] = [
    {
      name: "Bitcoin.se",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491440/bitcoinse_ynsp3u.png",
      url: "https://bitcoin.se",
    },
    {
      name: "Change NOW",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491436/changenow_mtal3l.png",
      url: "https://changenow.io",
    },
    {
      name: "Bitcoin.com",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491436/bitcoincom_sjhcfz.png",
      url: "https://bitcoin.com",
    },
    {
      name: "Kraken",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491434/kraken_iylbz6.png",
      url: "https://kraken.com",
    },
    {
      name: "Coinbase",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491433/coinbase_xwei2z.png",
      url: "https://coinbase.com",
    },
    {
      name: "NURI",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491434/nuri_ptozsb.png",
      url: "https://nuri.com",
    },
    {
      name: "EXMO",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491433/exmo_oyvjap.png",
      url: "https://exmo.com",
    },
    {
      name: "Bitonic",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491433/bitonic_yroan9.png",
      url: "https://bitonic.nl",
    },
    {
      name: "PAYEER",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491432/payeer_sbjguf.png",
      url: "https://payeer.com",
    },
    {
      name: "BTCX",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491432/btcx_xpqnnc.png",
      url: "https://btcx.com",
    },
    {
      name: "LocalBitcoins",
      logo: "https://res.cloudinary.com/dyp8gtllq/image/upload/v1744491432/localbitcoins_powbqf.png",
      url: "https://localbitcoins.com",
    },
  ];

  return (
    <div className="flex min-h-screen flex-col">
      <main className="flex-1">
        <section className="w-full">
          <div className="container px-4">
            
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-8 mt-12">
              {partners.map((partner) => (
                <Link
                  to={partner.url}
                  key={partner.name}
                  className="group relative flex items-center justify-center"
                  onMouseEnter={() => setHoveredLogo(partner.name)}
                  onMouseLeave={() => setHoveredLogo(null)}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <motion.div
                    className="absolute inset-0 rounded-lg bg-accent/0 transition-colors duration-300 group-hover:bg-accent/10"
                    initial={{ opacity: 0 }}
                    animate={{
                      opacity: hoveredLogo === partner.name ? 1 : 0,
                      scale: hoveredLogo === partner.name ? 1.05 : 1,
                    }}
                    transition={{ duration: 0.3 }}
                  />
                  <div className="relative h-16 w-full flex items-center justify-center">
                    <motion.div
                      initial={{ y: 0 }}
                      animate={{
                        y: hoveredLogo === partner.name ? -5 : 0,
                      }}
                      transition={{ duration: 0.3 }}
                      className="w-full h-full flex items-center justify-center"
                    >
                      <img
                        src={partner.logo || "/placeholder.svg"}
                        alt={partner.name}
                        width={150}
                        height={40}
                        className="grayscale max-h-10 w-auto object-contain opacity-70 group-hover:opacity-100 transition-opacity duration-300"
                      />
                    </motion.div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      </main>
    </div>
  );
}
