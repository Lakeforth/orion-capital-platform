import React from 'react';
import { KYC_PARTNERS } from '@/constants/kyc-partners';
import { Shield, Check, ExternalLink, Info } from 'lucide-react';

const KYCPartners: React.FC = () => {
  return (
    <div className="min-h-screen bg-background py-12 px-4 pb-24">
      <div className="max-w-6xl mx-auto space-y-12">
        {/* Header Section */}
        <div className="text-center space-y-5 max-w-3xl mx-auto">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[#52e5ab] text-sm font-bold mb-2">
            <Shield className="w-4 h-4" />
            Verified Partners
          </div>
          <h1 className="text-4xl md:text-5xl font-extrabold text-foreground tracking-tight leading-tight">
            Integrated KYC Ecosystem
          </h1>
          <p className="text-lg text-muted-foreground leading-relaxed font-medium">
            We've partnered with the world's leading cryptocurrency exchanges to provide a seamless, 
            secure, and instant identity verification experience across our entire network.
          </p>
        </div>

        {/* Partners Grid */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          {KYC_PARTNERS.map((partner) => (
            <a 
              key={partner.name}
              href={partner.url}
              target="_blank"
              rel="noopener noreferrer"
              className="group relative bg-card border border-border rounded-[2.5rem] p-10 flex flex-col items-center justify-center transition-all duration-500 hover:border-[#52e5ab]/40 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-2 cursor-pointer no-underline"
            >
              <div className="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition-opacity">
                <ExternalLink className="w-4 h-4 text-muted-foreground" />
              </div>
              
              <div className="relative w-24 h-24 mb-6 transition-transform duration-500 group-hover:scale-110">
                <div className="absolute inset-0 bg-emerald-500 rounded-full blur-[40px] opacity-0 group-hover:opacity-10 transition-opacity" />
                <img 
                  src={partner.logo} 
                  alt={partner.name}
                  className="w-full h-full object-contain grayscale group-hover:grayscale-0 transition-all duration-500 brightness-110"
                />
              </div>
              
              <h3 className="text-xs font-black text-foreground group-hover:text-[#52e5ab] transition-colors uppercase tracking-[0.2em] text-center">
                {partner.name}
              </h3>
              
              <div className="mt-5 flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/5 text-[9px] font-black text-[#52e5ab] uppercase tracking-wider border border-emerald-500/10">
                <Check className="w-2.5 h-2.5" />
                Sync Active
              </div>
            </a>
          ))}
        </div>

        {/* Info Section */}
        <div className="bg-card border border-border rounded-[3rem] p-10 md:p-16 relative overflow-hidden shadow-2xl shadow-black/5 mt-12">
          <div className="absolute top-0 right-0 w-80 h-80 bg-emerald-500/5 blur-[120px] rounded-full -mr-32 -mt-32" />
          
          <div className="grid md:grid-cols-2 gap-16 items-center relative z-10">
            <div className="space-y-8">
              <h2 className="text-3xl font-bold text-foreground leading-tight">
                How our integrated <br/>verification works
              </h2>
              <div className="space-y-6">
                {[
                  {
                    title: "Single Identity Profile",
                    desc: "Your verification status is shared across our secure network of partner exchanges instantly."
                  },
                  {
                    title: "Privacy First",
                    desc: "We use zero-knowledge protocols and advanced encryption to protect your sensitive data."
                  },
                  {
                    title: "Global Compliance",
                    desc: "Standardized KYC procedures that meet international regulatory and anti-money laundering requirements."
                  }
                ].map((item, i) => (
                  <div key={i} className="flex gap-5">
                    <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0 border border-emerald-500/10 text-[#52e5ab]">
                      <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]" />
                    </div>
                    <div>
                      <h4 className="font-bold text-foreground mb-1.5">{item.title}</h4>
                      <p className="text-sm text-muted-foreground leading-relaxed font-medium">{item.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
            
            <div className="bg-[#1E223D]/50 backdrop-blur-sm rounded-[2.5rem] p-10 border border-border/50 space-y-8 shadow-inner">
              <div className="flex items-center gap-4 text-[#52e5ab]">
                <div className="p-3 bg-emerald-500/10 rounded-2xl">
                    <Info className="w-6 h-6" />
                </div>
                <h4 className="font-bold text-xl">Service Limits</h4>
              </div>
              <p className="text-base text-muted-foreground leading-relaxed font-medium">
                Integrated KYC allows you to trade up to <span className="text-foreground font-bold">$50,000 USD</span> daily across all partner platforms 
                without repeating the verification process. 
              </p>
              
              <div className="space-y-5 pt-4">
                <div className="space-y-3">
                    <div className="flex justify-between text-[11px] font-black text-muted-foreground uppercase tracking-widest">
                        <span>Network Synchronization</span>
                        <span className="text-[#52e5ab]">99.9% Uptime</span>
                    </div>
                    <div className="h-2.5 w-full bg-muted/20 rounded-full overflow-hidden p-0.5">
                        <div className="h-full w-full bg-gradient-to-r from-[#52e5ab] to-emerald-400 rounded-full shadow-[0_0_15px_rgba(16,185,129,0.3)]" />
                    </div>
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                    <div className="bg-background/40 p-5 rounded-2xl border border-border/40">
                        <p className="text-[10px] font-bold text-muted-foreground uppercase mb-1">Response Time</p>
                        <p className="text-2xl font-black text-foreground">{'<'} 2s</p>
                    </div>
                    <div className="bg-background/40 p-5 rounded-2xl border border-border/40">
                        <p className="text-[10px] font-bold text-muted-foreground uppercase mb-1">Encryption</p>
                        <p className="text-2xl font-black text-foreground">AES-256</p>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default KYCPartners;
