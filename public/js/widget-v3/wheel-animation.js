export class WheelAnimation {
    constructor(state, renderer) {
        this.state = state;
        this.renderer = renderer;
    }

    async animate(prizeIndex, duration = 4000, spins = 5) {
        return new Promise((resolve) => {
            const prizes = this.state.get('prizes');
            let finalAngle = 0;

            if (prizeIndex >= 0 && prizeIndex < prizes.length) {
                finalAngle = this.renderer.getPrizeCenterAngle(prizeIndex);
            } else {
                finalAngle = 0 + Math.random() * 2 * Math.PI;
            }

            const targetRotation = 0 - finalAngle;
            const currentRotation = this.state.get('currentRotation');
            const finalRotation = currentRotation + (spins * 2 * Math.PI) + targetRotation;

            const startRotation = currentRotation;
            const rotationDiff = finalRotation - startRotation;
            const startTime = Date.now();

            const animate = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const newRotation = startRotation + rotationDiff * easeOut;

                this.state.set('currentRotation', newRotation);
                this.renderer.draw(newRotation);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.state.set('currentRotation', finalRotation);
                    this.renderer.draw(finalRotation);
                    resolve();
                }
            };

            animate();
        });
    }
}

