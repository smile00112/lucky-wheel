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

    drawSector(ctx, centerX, centerY, radius, startAngle, angle, color, useGradient = false, gradientStart = null, gradientEnd = null) {
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + angle);
        ctx.closePath();

        if (useGradient && gradientStart && gradientEnd) {
            const gradient = ctx.createRadialGradient(
                centerX, centerY, 0,
                centerX, centerY, radius
            );
            gradient.addColorStop(0, gradientStart);
            gradient.addColorStop(1, gradientEnd);
            ctx.fillStyle = gradient;
        } else {
            ctx.fillStyle = color;
        }

        ctx.fill();
        ctx.strokeStyle = color;
        ctx.lineWidth = 0.5;
        ctx.stroke();
    }

    drawPrizeImage(ctx, prizeImage, angle, radius, sectorIndex, offsetY = 0) {
        if (!prizeImage) return false;

        // Изображение по центру сегмента возле внешней дуги
        // Контекст уже повернут на центр сегмента, поэтому размещаем по оси Y
        const imageRadius = radius * 0.9;

        // Вычисляем длину внешней дуги секции
        const arcLength = radius * angle;
        // Размер изображения = 30% от длины внешней дуги
        const imageSize = arcLength * 0.3;

        // Позиция по центру сегмента (X=0) на радиусе близком к внешней дуге
        const imageX = 0;
        const imageY = -imageRadius + offsetY;

        ctx.save();
        ctx.translate(imageX, imageY);
        ctx.rotate(Math.PI); // Поворачиваем на 180°, чтобы верх был направлен к центру

        const imageAspectRatio = prizeImage.width / prizeImage.height;
        let drawHeight = imageSize;
        let drawWidth = drawHeight * imageAspectRatio;

        // Убрали белый круг вокруг изображения

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
        const baseFontSize = prize.font_size || 18;
        const fontBold = isMobile ? '' : 'bold';
        let fontSize;
        if (isMobile && prize.mobile_font_size) {
            fontSize = prize.mobile_font_size;
        } else if (isMobile) {
            fontSize = Math.round(baseFontSize * 0.8);
        } else {
            fontSize = baseFontSize;
        }

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = prize.text_color || '#fff';
        ctx.font = `${fontBold} ${fontSize}px Arial`;

        const lineHeight = fontSize * 1.3;
        const textRadius = radius * 0.5;

        // Поддержка переносов строк: \n и <br> / <br />
        const processText = (text) => {
            if (!text) return [];
            return text
                .replace(/<br\s*\/?>/gi, '\n')
                .split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);
        };

        // Используем mobile_name для мобильной версии, если заполнено
        const prizeName = (isMobile && prize.mobile_name) ? prize.mobile_name : prize.name;
        const nameLines = processText(prizeName);
        //const descLines = (prize.description && !isMobile) ? processText(prize.description) : [];
        const descLines = (prize.description) ? processText(prize.description) : [];
        const totalLines = nameLines.length + descLines.length;

        // Вычисляем начальный yOffset для центрирования всего блока текста
        const totalHeight = totalLines * lineHeight;
        let yOffset = -totalHeight / 2 + lineHeight / 2;

        nameLines.forEach((line, index) => {
            ctx.font = `${fontBold} ${fontSize}px Arial`;
            ctx.fillText(line, textRadius, yOffset);
            yOffset += lineHeight;
        });

        //для  мобилок уменьшаем промежуток между названием и описанием приза
        if(isMobile)
            yOffset -= lineHeight * 0.3;

        descLines.forEach((line) => {
            ctx.font = `${fontSize - 1}px Arial`;
            ctx.fillText(line, textRadius, yOffset);
            yOffset += lineHeight;
        });
    }


    draw(rotation = 0) {
        const ctx = this.state.get('ctx');
        const canvas = this.state.get('canvas');
        const prizes = this.state.get('prizes');
        const centerX = this.state.get('centerX');
        const centerY = this.state.get('centerY');
        var radius = this.state.get('radius');
        const prizeImages = this.state.get('prizeImages');

        //уменьшим максимальный радиус для прорисовки окантовки колеса
        radius = radius - 5;

        if (!ctx || !canvas) return;
        if (!prizes || prizes.length === 0) return;
        if (!radius || radius <= 0) return;
        if (!centerX || !centerY) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Убрали белый круглый фон колеса
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
            const useGradient = prize.use_gradient || false;
            const gradientStart = prize.gradient_start || null;
            const gradientEnd = prize.gradient_end || null;
            this.drawSector(ctx, centerX, centerY, radius, currentAngle, equalAngle, color, useGradient, gradientStart, gradientEnd);

            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(currentAngle + equalAngle / 2);

            const prizeImage = prizeImages[prize.id];
            const sectorView = prize.sector_view || 'text_with_image';
            const hasImage = prizeImage && prize.image;

            // Определяем, что показывать в зависимости от sector_view
            if (sectorView === 'only_image' && hasImage) {
                // Только изображение - смещаем выше центра
                const imageOffsetY = -radius * 0.15; // Смещение выше центра
                this.drawPrizeImage(ctx, prizeImage, equalAngle, radius, index, imageOffsetY);
            } else if (sectorView === 'only_text') {
                // Только текст
                this.drawPrizeText(ctx, prize, equalAngle, radius);
            } else {
                // text_with_image (по умолчанию) - показываем и изображение и текст
                if (hasImage) {
                    this.drawPrizeImage(ctx, prizeImage, equalAngle, radius, index);
                }
                this.drawPrizeText(ctx, prize, equalAngle, radius);
            }

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
        const radius = canvasSize * 0.5;

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

