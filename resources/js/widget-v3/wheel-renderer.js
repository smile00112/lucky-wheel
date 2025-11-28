import { Utils } from './utils.js';

export class WheelRenderer {
    constructor(state) {
        this.state = state;
        this.arrowImage = null;
        this.loadArrowImage();
    }

    async loadArrowImage() {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                this.arrowImage = img;
                resolve(img);
            };
            img.onerror = () => {
                console.warn('Failed to load arrow image');
                reject();
            };
            img.src = window.APP_URL + '/images/wheel/wheel-arrow.png';
        });
    }

    drawSector(ctx, centerX, centerY, radius, startAngle, angle, color) {
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + angle);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = color;
        ctx.lineWidth = 0.5;
        ctx.stroke();
    }

    drawPrizeImage(ctx, prizeImage, angle, radius, sectorIndex) {
        if (!prizeImage) return false;

        const imageRadius = radius * 1.02;
        const imageAngle = angle / 2;

        // Вычисляем длину внешней дуги секции
        const arcLength = radius * angle;
        // Размер изображения = 30% от длины внешней дуги
        const imageSize = arcLength * 0.3;

        const imageX = Math.cos(imageAngle) * imageRadius;
        const imageY = 0;

        ctx.save();
        ctx.translate(imageX, imageY);
        ctx.rotate(Math.PI * 1.5);

        const imageAspectRatio = prizeImage.width / prizeImage.height;
        let drawHeight = imageSize;
        let drawWidth = drawHeight * imageAspectRatio;

        const whiteCircleRadius = Math.max(drawWidth, drawHeight) / 2 * 1.9;

        // Смещение фона от центра (положительное значение = дальше от центра)
        const backgroundOffset = 15; // пикселей, можно настроить
        //ctx.save(); // сохраняем текущее состояние
        //ctx.translate(0, -backgroundOffset); // смещаем только для белого круга (отрицательное Y = дальше от центра

        ctx.beginPath();
        ctx.arc(0, whiteCircleRadius * 0.4, whiteCircleRadius, 0, 2 * Math.PI);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 0.5;
        ctx.stroke();

        ctx.drawImage(
            prizeImage,
            -drawWidth / 2,
            -drawHeight / 2,
            drawWidth,
            drawHeight
        );

        ctx.restore();
        return true;
    }

    drawPrizeText(ctx, prize, angle, radius) {
        const isMobile = this.state.get('isMobile') || window.innerWidth <= 768;
        const fontSize = isMobile ? 12 : 13;

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = prize.text_color || '#fff';
        ctx.font = `bold ${fontSize}px Arial`;

        const lineHeight = fontSize * 1.3;
        let yOffset = 0;
        //отступ текска от центра
        const textRadius = radius * 0.6;

        ctx.fillText(prize.name, textRadius, yOffset);

        if (prize.description && !isMobile) {
            yOffset += lineHeight;
            ctx.font = `${fontSize - 1}px Arial`;
            ctx.fillText(prize.description, textRadius, yOffset);
        }
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

        // Рисуем белый круглый фон колеса
        const backgroundRadius = canvas.width / 2;
        ctx.beginPath();
        ctx.arc(centerX, centerY, backgroundRadius, 0, 2 * Math.PI);
        ctx.fillStyle = '#ffffff';
        ctx.fill();

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
                this.drawPrizeImage(ctx, prizeImage, equalAngle, radius, index);
            }
            this.drawPrizeText(ctx, prize, equalAngle, radius);

            ctx.restore();
            currentAngle += equalAngle;
        });

        if (this.arrowImage) {
            ctx.save();
            ctx.translate(centerX, centerY);
            const arrowWidth = this.arrowImage.width;
            const arrowHeight = this.arrowImage.height;
            ctx.drawImage(
                this.arrowImage,
                -arrowWidth / 2,
                -arrowHeight / 2,
                arrowWidth,
                arrowHeight
            );
            ctx.restore();
        }
    }

    init(canvasElement) {
        if (!canvasElement) {
            console.error('Canvas element not found');
            return;
        }

        const isMobile = window.innerWidth <= 768;
        let canvasSize;

        if (isMobile) {
            // На мобильных используем размер контейнера
            const container = canvasElement.closest('.wheel-container');
            const containerWidth = container ? container.clientWidth : 286;
            // Учитываем padding контейнера (примерно 40px с обеих сторон)
            canvasSize = Math.min(containerWidth, window.innerWidth - 40);
        } else {
            canvasSize = 600;
        }

        canvasElement.width = canvasSize;
        canvasElement.height = canvasSize;

        const centerX = canvasSize / 2;
        const centerY = canvasSize / 2;
        const radius = canvasSize * 0.46;

        this.state.set('canvas', canvasElement);
        this.state.set('ctx', canvasElement.getContext('2d'));
        this.state.set('centerX', centerX);
        this.state.set('centerY', centerY);
        this.state.set('radius', radius);
        this.state.set('isMobile', isMobile);
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
