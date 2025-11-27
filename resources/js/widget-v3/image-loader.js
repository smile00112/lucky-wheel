export class ImageLoader {
    constructor(state, config) {
        this.state = state;
        this.config = config;
    }

    async loadPrizeImages(prizes) {
        console.log('[LuckyWheel] loadPrizeImages() started, prizes count:', prizes.length);
        const imagePromises = prizes.map(async (prize) => {
            if (!prize.image) {
                console.log('[LuckyWheel] No image for prize:', prize.id);
                this.setPrizeImage(prize.id, null);
                return;
            }

            return new Promise((resolve) => {
                const img = new Image();
                const timeout = 10000; // 10 секунд
                let timeoutId;

                console.log('[LuckyWheel] Loading image for prize:', prize.id, prize.image);

                if (prize.image.startsWith('http://') || prize.image.startsWith('https://')) {
                    const currentOrigin = window.location.origin;
                    const imageUrl = new URL(prize.image);
                    if (imageUrl.origin !== currentOrigin) {
                        img.crossOrigin = 'anonymous';
                    }
                }

                const cleanup = () => {
                    if (timeoutId) clearTimeout(timeoutId);
                    img.onload = null;
                    img.onerror = null;
                };

                img.onload = () => {
                    cleanup();
                    console.log('[LuckyWheel] Image loaded successfully:', prize.id);
                    this.setPrizeImage(prize.id, img);
                    resolve();
                };

                img.onerror = () => {
                    cleanup();
                    console.warn('[LuckyWheel] Failed to load image for prize:', prize.id, prize.image);
                    this.setPrizeImage(prize.id, null);
                    resolve();
                };

                timeoutId = setTimeout(() => {
                    cleanup();
                    console.warn('[LuckyWheel] Image load timeout for prize:', prize.id, prize.image);
                    this.setPrizeImage(prize.id, null);
                    resolve();
                }, timeout);

                img.src = prize.image;
            });
        });

        await Promise.all(imagePromises);
        console.log('[LuckyWheel] loadPrizeImages() completed');
    }

    setPrizeImage(prizeId, image) {
        const prizeImages = this.state.get('prizeImages');
        prizeImages[prizeId] = image;
        this.state.set('prizeImages', { ...prizeImages });
    }
}

