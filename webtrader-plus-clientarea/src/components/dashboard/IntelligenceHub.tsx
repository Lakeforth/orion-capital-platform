import React from "react";
import {
  Zap,
  CheckCircle2,
  ChevronRight,
  Sparkles,
  Brain,
  Lightbulb,
} from "lucide-react";

const IntelligenceHub: React.FC = () => {
  return (
    <div className="space-y-6">
      {/* Main Section Container - White Background & Rounded */}
      <div className="bg-white rounded-xl p-6 md:p-8 space-y-8 shadow-sm border border-gray-100">
        {/* Header - Now Inside the white container */}
        <div className="space-y-1">
          <h2 className="text-[28px] font-bold text-[#00234B]">
            Intelligence Hub
          </h2>
          <p className="text-gray-500 text-sm">
            Real-time AI-driven market analysis and recommendations.
          </p>
        </div>

        <div className="space-y-6">
          {/* Main Intelligence Card */}
          <div className="bg-white rounded-xl p-8 relative overflow-hidden border-[1.5px] border-[#EBF5FF] shadow-sm">
            <div className="flex justify-between items-start mb-6">
              <div className="flex gap-4 items-center">
                <div className="w-12 h-12 rounded-xl bg-[#00234B] flex items-center justify-center">
                  <Zap className="text-white fill-white" size={24} />
                </div>
                <div>
                  <div className="flex items-center gap-1.5">
                    <h3 className="text-lg font-bold text-[#00234B]">
                      Gemini AI Engine
                    </h3>
                    <CheckCircle2 className="text-[#3B82F6]" size={16} />
                  </div>
                  <p className="text-xs text-gray-400 font-medium">
                    Today, 10:42 AM • Server: Cluster-7
                  </p>
                </div>
              </div>
              <span className="px-4 py-1.5 bg-[#5EB5F8] text-white text-[10px] font-bold rounded-full uppercase tracking-wider shadow-sm">
                Priority
              </span>
            </div>

            <h2 className="text-xl md:text-2xl font-bold text-[#00234B] mb-6 leading-tight tracking-tight">
              Potential Liquidity Surge in Mid-Cap Tech Sector
            </h2>

            <p className="text-gray-500 leading-relaxed mb-8 text-sm">
              Our neural network has detected abnormal order flow patterns
              across the NASDAQ Global Select Market. Quantitative indicators
              suggest a high- probability liquidity surge in semi-conductor
              mid-caps over the next 4-hour window. Institutional buy- pressure
              is currently exceeding the 10-day moving average by 42%.
            </p>

            <div className="grid grid-cols-3 gap-4 mb-8 pt-8 border-t border-gray-100">
              <div>
                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-2">
                  Confidence
                </p>
                <p className="text-2xl font-bold text-[#22C55E]">94%</p>
              </div>
              <div>
                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-2">
                  Risk Level
                </p>
                <p className="text-2xl font-bold text-[#006591]">MODERATE</p>
              </div>
              <div>
                <p className="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-2">
                  Volatility
                </p>
                <p className="text-2xl font-bold text-[#0F172A]">LOW</p>
              </div>
            </div>

            <div className="flex gap-4">
              <button className="bg-[#1E293B] hover:bg-black text-white px-8 py-3 rounded-xl font-bold text-sm transition-all shadow-lg shadow-gray-200">
                Execute Strategy
              </button>
              <button className="bg-white hover:bg-gray-50 text-[#00234B] border border-gray-200 px-8 py-3 rounded-xl font-bold text-sm transition-all">
                Analyze Further
              </button>
            </div>
          </div>

          {/* Recommendation History Card */}
          <div className="bg-white rounded-[24px] p-8 border-[1.5px] border-[#EBF5FF] shadow-sm">
            <div className="flex justify-between items-center mb-8">
              <h3 className="text-xl font-bold text-[#00234B]">
                Recommendation History
              </h3>
              <button className="text-[#006591] font-bold text-sm hover:underline tracking-tight">
                Export CSV
              </button>
            </div>

            <div className="space-y-4">
              {/* Item 1 */}
              <div className="flex items-center gap-4 p-5 rounded-2xl bg-[#F4F7FF] group cursor-pointer hover:bg-[#EDF2FF] transition-all">
                <div className="w-12 h-12 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                  <Sparkles className="text-[#006591]" size={22} />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-bold text-[#00234B] text-[15px] truncate">
                    Bullish Convergence: BTC/USD
                  </h4>
                  <p className="text-xs text-gray-400 font-medium mt-0.5">
                    Gemini AI • 2h ago
                  </p>
                </div>
                <div className="flex items-center gap-4">
                  <span className="px-4 py-1.5 bg-[#D1FAE5] text-[#059669] text-[10px] font-bold rounded-full uppercase tracking-wider">
                    Successful
                  </span>
                  <ChevronRight
                    className="text-gray-300 group-hover:text-gray-500 transition-colors"
                    size={20}
                  />
                </div>
              </div>

              {/* Item 2 */}
              <div className="flex items-center gap-4 p-5 rounded-2xl bg-[#F4F7FF] group cursor-pointer hover:bg-[#EDF2FF] transition-all">
                <div className="w-12 h-12 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                  <Lightbulb className="text-[#006591]" size={22} />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-bold text-[#00234B] text-[15px] truncate">
                    Risk Alert: Crude Oil Futures
                  </h4>
                  <p className="text-xs text-gray-400 font-medium mt-0.5">
                    Alpha Predictor • 5h ago
                  </p>
                </div>
                <div className="flex items-center gap-4">
                  <span className="px-4 py-1.5 bg-[#E2E8F0] text-[#64748B] text-[10px] font-bold rounded-full uppercase tracking-wider">
                    Pending
                  </span>
                  <ChevronRight
                    className="text-gray-300 group-hover:text-gray-500 transition-colors"
                    size={20}
                  />
                </div>
              </div>

              {/* Item 3 */}
              <div className="flex items-center gap-4 p-5 rounded-2xl bg-[#F4F7FF] group cursor-pointer hover:bg-[#EDF2FF] transition-all">
                <div className="w-12 h-12 rounded-xl bg-white border border-gray-100 flex items-center justify-center flex-shrink-0 shadow-sm">
                  <Brain className="text-[#006591]" size={22} />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-bold text-[#00234B] text-[15px] truncate">
                    Portfolio Rebalance Suggestion
                  </h4>
                  <p className="text-xs text-gray-400 font-medium mt-0.5">
                    Deep Insight • Yesterday
                  </p>
                </div>
                <div className="flex items-center gap-4">
                  <span className="px-4 py-1.5 bg-[#FEE2E2] text-[#EF4444] text-[10px] font-bold rounded-full uppercase tracking-wider">
                    Dismissed
                  </span>
                  <ChevronRight
                    className="text-gray-300 group-hover:text-gray-500 transition-colors"
                    size={20}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default IntelligenceHub;
