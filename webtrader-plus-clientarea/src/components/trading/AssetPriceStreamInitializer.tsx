import { useAssetWebSocket } from "@/hooks/useAssetWebsocket";

export default function AssetPriceStreamInitializer() {
  useAssetWebSocket();
  return null;
}

