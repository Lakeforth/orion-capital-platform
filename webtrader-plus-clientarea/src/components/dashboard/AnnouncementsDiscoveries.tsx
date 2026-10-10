import React, { useState } from "react";
import { X, User } from "lucide-react";
import { useAnnouncementsQuery } from "@/services/announcements/announcements-queries";
import { Announcement } from "@/services/announcements/announcements-types";
import { formatDistanceToNow } from "date-fns";

interface DiscoveryItem extends Announcement {
  type?: "deposit";
  user?: string;
  location?: string;
  amount?: string;
}

const AnnouncementsDiscoveries: React.FC = () => {
  const { data, isLoading } = useAnnouncementsQuery();
  const [hiddenIds, setHiddenIds] = useState<string[]>([]);

  const isEnabled = data?.data?.enabled ?? false;
  const announcements = (data?.data?.items as DiscoveryItem[]) || [];
  const displayItems = announcements.filter(
    (item) => !hiddenIds.includes(item.id),
  );

  const handleHide = (id: string) => {
    setHiddenIds((prev) => [...prev, id]);
  };

  if (!isLoading && (!isEnabled || displayItems.length === 0)) {
    return null;
  }

  const renderItem = (item: DiscoveryItem) => {
    const timeAgo = formatDistanceToNow(new Date(item.created_at), {
      addSuffix: true,
    }).replace("about ", "");

    if (item.type === "deposit") {
      return (
        <div
          key={item.id}
          className="relative border border-gray-100 rounded-2xl p-4 flex gap-4 items-center group bg-white hover:border-gray-200 transition-all"
        >
          <div className="w-12 h-12 rounded-xl bg-[#1A1D1F] flex items-center justify-center overflow-hidden flex-shrink-0">
            <div className="w-full h-full bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center">
              <User className="text-white/40" size={24} />
            </div>
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-0.5">
              <span className="font-bold text-[#00234B] text-[15px]">
                {item.user} from {item.location}
              </span>
              <span className="text-xs text-gray-400">{timeAgo}</span>
            </div>
            <p className="text-[#00A041] font-bold text-[15px]">
              just deposited {item.amount}
            </p>
          </div>
          <button
            onClick={() => handleHide(item.id)}
            className="text-gray-300 hover:text-gray-500 transition-colors"
          >
            <X size={18} />
          </button>
        </div>
      );
    }

    const isAnalysis = item.category === "analysis";

    return (
      <div
        key={item.id}
        className="relative bg-[#EFF4FF] border-y-gray-100 border-r-gray-100 border-l-[6px] border-l-[#006591] rounded-2xl p-5 flex gap-4 items-start group transition-all"
      >
        <div className="flex-1">
          <div className="flex items-center gap-3 mb-2">
            {isAnalysis && (
              <span className="px-2 py-0.5 bg-[#E7F5FF] text-[#006591] text-[10px] font-bold rounded uppercase tracking-widest">
                Analysis
              </span>
            )}
            <span className="text-xs text-gray-400">{timeAgo}</span>
          </div>
          <h4 className="font-bold text-[#00234B] text-[17px] mb-1.5">
            {item.title}
          </h4>
          <p className="text-sm text-gray-500 leading-relaxed line-clamp-2">
            {item.body}
          </p>
        </div>
        <button
          onClick={() => handleHide(item.id)}
          className="text-gray-300 hover:text-gray-500 transition-colors mt-1"
        >
          <X size={18} />
        </button>
      </div>
    );
  };

  return (
    <div className="bg-white rounded-[24px] p-8 shadow-sm border border-gray-100">
      <div className="flex justify-between items-center mb-8">
        <h3 className="text-[28px] font-bold text-[#00234B]">
          Announcement & Discoveries
        </h3>
        <button className="text-[#FF7700] font-bold text-lg hover:underline transition-all">
          See all activity
        </button>
      </div>

      <div className="space-y-4 h-[500px] overflow-y-auto pr-2 custom-scrollbar">
        {isLoading ? (
          <div className="flex justify-center py-10">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF7700]"></div>
          </div>
        ) : (
          displayItems.map(renderItem)
        )}
      </div>

      <style>{`
        .custom-scrollbar::-webkit-scrollbar {
          width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
          background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
          background: #E5E7EB;
          border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
          background: #D1D5DB;
        }
      `}</style>
    </div>
  );
};

export default AnnouncementsDiscoveries;
