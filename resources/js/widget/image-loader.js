export class ImageLoader {
    constructor(state, config) {
        this.state = state;
        this.config = config;
    }

    async loadPrizeImages(prizes) {
        const imagePromises = prizes.map(async (prize) => {
            if (!prize.image) {
                this.setPrizeImage(prize.id, null);
                return;
            }

            return new Promise((resolve) => {
                const img = new Image();
                const timeout = 10000; // 10 секунд
                let timeoutId;

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
                    this.setPrizeImage(prize.id, img);
                    resolve();
                };

                img.onerror = () => {
                    cleanup();
                    console.warn('Failed to load image for prize:', prize.id, prize.image);
                    this.setPrizeImage(prize.id, null);
                    resolve();
                };

                timeoutId = setTimeout(() => {
                    cleanup();
                    console.warn('Image load timeout for prize:', prize.id, prize.image);
                    this.setPrizeImage(prize.id, null);
                    resolve();
                }, timeout);

                img.src = prize.image;
            });
        });

        await Promise.all(imagePromises);
    }

    setPrizeImage(prizeId, image) {
        const prizeImages = this.state.get('prizeImages');
        prizeImages[prizeId] = image;
        this.state.set('prizeImages', { ...prizeImages });
    }
}

