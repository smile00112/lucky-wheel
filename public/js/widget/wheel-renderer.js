import { Utils } from './utils.js';

export class WheelRenderer {
    constructor(state) {
        this.state = state;
    }

    calculateOptimalTextSize(text, sectorWidth, sectorHeight, minFontSize = 12, maxFontSize = 100) {
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');

        let bestFontSize = minFontSize;
        let bestLines = [];

        while (maxFontSize - minFontSize > 1) {
            const fontSize = Math.floor((minFontSize + maxFontSize) / 2);
            tempCtx.font = `bold ${fontSize}px Arial`;

            const singleLineMetrics = tempCtx.measureText(text);
            let lines = [];

            if (singleLineMetrics.width <= sectorWidth * 0.9) {
                lines = [text];
            } else {
                const words = text.split(' ');
                let firstLine = '';
                let secondLine = '';

                for (let i = 0; i < words.length; i++) {
                    const testFirstLine = firstLine ? firstLine + ' ' + words[i] : words[i];
                    const testMetrics = tempCtx.measureText(testFirstLine);

                    if (testMetrics.width > sectorWidth * 0.9 && firstLine) {
                        secondLine = words.slice(i).join(' ');
                        break;
                    }
                    firstLine = testFirstLine;
                }

                if (!secondLine && words.length > 1) {
                    const midPoint = Math.floor(words.length / 2);
                    firstLine = words.slice(0, midPoint).join(' ');
                    secondLine = words.slice(midPoint).join(' ');
                }

                const firstMetrics = tempCtx.measureText(firstLine);
                const secondMetrics = tempCtx.measureText(secondLine);

                if (firstMetrics.width <= sectorWidth * 0.9 && secondMetrics.width <= sectorWidth * 0.9) {
                    lines = [firstLine, secondLine];
                } else {
                    lines = [text];
                }
            }

            const lineHeight = fontSize * 1.2;
            const totalHeight = lines.length * lineHeight;

            if (totalHeight <= sectorHeight * 0.8 && lines.every(line => tempCtx.measureText(line).width <= sectorWidth * 0.9)) {
                bestFontSize = fontSize;
                bestLines = lines;
                minFontSize = fontSize;
            } else {
                maxFontSize = fontSize;
            }
        }

        if (bestLines.length === 0) {
            bestFontSize = minFontSize;
            tempCtx.font = `bold ${bestFontSize}px Arial`;
            const words = text.split(' ');
            const midPoint = Math.floor(words.length / 2);
            const firstLine = words.slice(0, midPoint).join(' ');
            const secondLine = words.slice(midPoint).join(' ');
            bestLines = secondLine ? [firstLine, secondLine] : [firstLine];
        }

        return { fontSize: bestFontSize, lines: bestLines };
    }

    drawSector(ctx, centerX, centerY, radius, startAngle, angle, color) {
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + angle);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();
    }

    drawPrizeImage(ctx, prizeImage, angle, radius) {
        const midRadius = radius * 0.65;
        const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
        const sectorHeight = radius * 0.8;

        const imageAspectRatio = prizeImage.width / prizeImage.height;
        const sectorAspectRatio = sectorWidth / sectorHeight;

        let imageWidth, imageHeight;
        if (imageAspectRatio > sectorAspectRatio) {
            imageWidth = sectorWidth * 0.95;
            imageHeight = imageWidth / imageAspectRatio;
        } else {
            imageHeight = sectorHeight * 0.95;
            imageWidth = imageHeight * imageAspectRatio;
        }

        const imageDistance = midRadius;
        const imageX = imageDistance;
        const imageY = 0;

        ctx.save();
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.arc(0, 0, radius * 0.98, -angle / 2 - 0.05, angle / 2 + 0.05);
        ctx.closePath();
        ctx.clip();

        ctx.save();
        ctx.translate(imageX, imageY);
        ctx.rotate(1.5);

        try {
            ctx.drawImage(
                prizeImage,
                -imageWidth / 2,
                -imageHeight / 2,
                imageWidth,
                imageHeight
            );
        } catch (e) {
            console.warn('Error drawing image:', e);
            ctx.restore();
            ctx.restore();
            return false;
        }

        ctx.restore();
        ctx.restore();
        return true;
    }

    drawPrizeText(ctx, prize, angle, radius) {
        const midRadius = radius * 0.65;
        const sectorWidth = 2 * Math.sin(angle / 2) * midRadius;
        const sectorHeight = radius * 0.8;

        const textConfig = this.calculateOptimalTextSize(prize.name, sectorWidth, sectorHeight);

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#fff';
        ctx.font = `bold ${textConfig.fontSize}px Arial`;

        const lineHeight = textConfig.fontSize * 1.2;
        const startY = -(textConfig.lines.length - 1) * lineHeight / 2;
        textConfig.lines.forEach((line, index) => {
            ctx.fillText(line, radius * 0.6, startY + index * lineHeight);
        });
    }

    draw(rotation = 0) {
        const ctx = this.state.get('ctx');
        const canvas = this.state.get('canvas');
        const prizes = this.state.get('prizes');
        const centerX = this.state.get('centerX');
        const centerY = this.state.get('centerY');
        const radius = this.state.get('radius');
        const prizeImages = this.state.get('prizeImages');

        if (!ctx || !canvas) return;
        if (!prizes || prizes.length === 0) return;
        if (!radius || radius <= 0) return;
        if (!centerX || !centerY) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const totalAngle = 2 * Math.PI;
        const equalAngle = totalAngle / prizes.length;
        let currentAngle = -Math.PI / 2 + rotation;

        prizes.forEach((prize, index) => {
            const color = prize.color || Utils.getColorByIndex(index);
            this.drawSector(ctx, centerX, centerY, radius, currentAngle, equalAngle, color);

            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(currentAngle + equalAngle / 2);

            const prizeImage = prizeImages[prize.id];
            if (prizeImage && prize.image) {
                const imageDrawn = this.drawPrizeImage(ctx, prizeImage, equalAngle, radius);
                if (!imageDrawn) {
                    this.drawPrizeText(ctx, prize, equalAngle, radius);
                }
            } else {
                this.drawPrizeText(ctx, prize, equalAngle, radius);
            }

            ctx.restore();
            currentAngle += equalAngle;
        });
    }

    init(canvasElement) {
        if (!canvasElement) {
            console.error('Canvas element not found');
            return;
        }

        const container = canvasElement.parentElement;
        if (!container) {
            console.error('Canvas container not found');
            return;
        }

        // Получаем размеры контейнера, если они еще не установлены, используем минимальные
        let containerWidth = container.clientWidth;
        if (!containerWidth || containerWidth === 0) {
            containerWidth = 400; // Значение по умолчанию
        }

        const size = Math.max(Math.min(containerWidth - 20, 400), 200); // Минимум 200px

        canvasElement.width = size;
        canvasElement.height = size;

        const centerX = size / 2;
        const centerY = size / 2;
        const radius = Math.max(Math.min(centerX, centerY) - 10, 50); // Минимум 50px радиуса

        this.state.set('canvas', canvasElement);
        this.state.set('ctx', canvasElement.getContext('2d'));
        this.state.set('centerX', centerX);
        this.state.set('centerY', centerY);
        this.state.set('radius', radius);
    }

    findPrizeIndex(prizeId) {
        const prizes = this.state.get('prizes');
        return prizes.findIndex(p => p.id === prizeId);
    }

    getPrizeCenterAngle(prizeIndex) {
        const prizes = this.state.get('prizes');
        const totalAngle = 2 * Math.PI;
        const equalAngle = totalAngle / prizes.length;
        let cumulativeAngle = -Math.PI / 2;

        for (let i = 0; i < prizes.length; i++) {
            if (i === prizeIndex) {
                return cumulativeAngle + equalAngle / 2;
            }
            cumulativeAngle += equalAngle;
        }

        return cumulativeAngle;
    }

    calculateRotationForPrize(prizeId) {
        const prizeIndex = this.findPrizeIndex(prizeId);
        if (prizeIndex === -1) {
            console.warn('Prize not found:', prizeId);
            return 0;
        }

        const prizeCenterAngle = this.getPrizeCenterAngle(prizeIndex);
        const rotation = -Math.PI / 2 - prizeCenterAngle;
        return Utils.normalizeAngle(rotation);
    }
}

