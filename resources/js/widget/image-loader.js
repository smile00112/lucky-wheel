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

                if (prize.image.startsWith('http://') || prize.image.startsWith('https://')) {
                    const currentOrigin = window.location.origin;
                    const imageUrl = new URL(prize.image);
                    if (imageUrl.origin !== currentOrigin) {
                        img.crossOrigin = 'anonymous';
                    }
                }

                img.onload = () => {
                    this.setPrizeImage(prize.id, img);
                    resolve();
                };

                img.onerror = () => {
                    console.warn('Failed to load image for prize:', prize.id, prize.image);
                    this.setPrizeImage(prize.id, null);
                    resolve();
                };

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

