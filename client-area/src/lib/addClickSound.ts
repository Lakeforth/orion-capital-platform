import useSoundStore from "@/store/soundStore";

/**
 * Adds a click sound to the entire application
 * Call this function once during app initialization
 */
export function addClickSound() {
    // Define proper type for AudioContext with cross-browser support
    type AudioContextType = typeof AudioContext

    // Get the appropriate constructor without using 'any'
    const getAudioContext = (): AudioContextType | null => {
        if (window.AudioContext) {
            return window.AudioContext;
        } else if ('webkitAudioContext' in window) {
            // Use type assertion with 'unknown' as intermediate step to avoid 'any'
            return (window as {webkitAudioContext: AudioContextType}).webkitAudioContext;
        }
        return null;
    };

    const AudioContextClass = getAudioContext();

    if (!AudioContextClass) {
        console.warn('Web Audio API not supported in this browser');
        return;
    }

    let audioContext: AudioContext | null = null;

    // Function to initialize audio context on first user interaction
    const initAudioContext = () => {
        if (!audioContext) {
            audioContext = new AudioContextClass();
        }
    };

    // Function to play the click sound
    const playClickSound = () => {
        // Check if sound is enabled from the store
        const isSoundEnabled = useSoundStore.getState().isSoundEnabled;

        // Only play sound if it's enabled and audio context exists
        if (!isSoundEnabled || !audioContext) return;

        try {
            // Create a short "chick" sound (higher pitched, shorter)
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(3000, audioContext.currentTime); // Higher frequency for "chick"

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.2, audioContext.currentTime + 0.005); // Quick ramp up

            // Very fast decay for a crisp "chick" sound
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.03); // Faster fade

            // Connect the nodes
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Play the sound for a very short time (30ms)
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.03);
        } catch (err) {
            console.error('Error playing click sound:', err);
        }
    };

    // Add click event listener to the document
    document.addEventListener('click', () => {
        // Initialize context if needed (requires user interaction)
        initAudioContext();

        // Play the click sound
        playClickSound();
    });

    // Pre-initialize on first user interaction
    const firstInteractionEvents = ['mousedown', 'keydown', 'touchstart'];
    const initAndRemove = () => {
        initAudioContext();
        firstInteractionEvents.forEach(event => {
            document.removeEventListener(event, initAndRemove);
        });
    };

    firstInteractionEvents.forEach(event => {
        document.addEventListener(event, initAndRemove, { once: true });
    });
}
