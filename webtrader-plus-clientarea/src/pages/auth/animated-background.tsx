import { useEffect, useState } from "react";

export default function AnimatedBackground() {
  const [windowSize, setWindowSize] = useState({
    width: typeof window !== "undefined" ? window.innerWidth : 1200,
    height: typeof window !== "undefined" ? window.innerHeight : 800,
  });
  console.log(windowSize);

  useEffect(() => {
    const handleResize = () => {
      setWindowSize({
        width: window.innerWidth,
        height: window.innerHeight,
      });
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  return (
    <div
      className="fixed inset-0 overflow-hidden"
      style={{
        backgroundImage:
          "url('https://res.cloudinary.com/dyp8gtllq/image/upload/v1744366498/4f431bd3-9c93-4084-9fe5-1c44c8877731_dcleo2.png')", // Path to the image
        backgroundSize: "cover",
        backgroundPosition: "center",
        filter: "blur(8px)",
      }}
    ></div>
  );
}
