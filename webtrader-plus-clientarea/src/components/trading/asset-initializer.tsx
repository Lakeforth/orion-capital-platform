import { useEffect } from "react";
import useAssetStore from "@/store/assetStore";

export default function AssetInitializer() {
  // const { fetchAssets, isLoading, error, assets } = useAssetStore();
  const fetchAssets = useAssetStore((state) => state.fetchAssets);

  useEffect(() => {
    console.log("AssetInitializer - Fetching assets");
    fetchAssets().catch((err) => {
      console.error("Error fetching assets:", err);
    });
  }, [fetchAssets]);

  // useEffect(() => {
  //   console.log("AssetInitializer - Assets loaded:", assets.length);
  //   console.log("AssetInitializer - Loading state:", isLoading);
  //   console.log("AssetInitializer - Error state:", error);
  // }, [assets, isLoading, error]);

  return null;
}
