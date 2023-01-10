document.addEventListener('DOMContentLoaded', function() {
    let arrayCanvas = getElements();
    if (!!arrayCanvas) {
        drawDials(arrayCanvas);
        popupInfo();
    }
}, false);

jQuery( document ).ajaxComplete(function() {
    let arrayCanvas = getElements();
    if (!!arrayCanvas) {
        drawDials(arrayCanvas);
        popupInfo();
    }
});
function popupInfo(){

    // Popup info button
    let popupBg = document.querySelector('.popup__bg');
    let popup = document.querySelector('.popup');
    let openPopupButtons = document.querySelectorAll('.open-popup');
    let closePopupButton = document.querySelector('.close-popup');

    var buttonsInfo = document.querySelectorAll('.open-popup');
    Array.from(buttonsInfo).forEach(function(button){
        button.addEventListener('click', function(e) {
            titlePopup.textContent = document.getElementById(e.target.className + "-title").textContent ?? 0;
            descriptionPopup.textContent = document.getElementById(e.target.className+ "-description").textContent ?? 0;
        })
    });

    if (!!openPopupButtons) {
        openPopupButtons.forEach((button) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                popupBg.classList.add('active');
                popup.classList.add('active');
            })
        });
    }
    if (!!closePopupButton) {
        closePopupButton.addEventListener('click', () => {
            popupBg.classList.remove('active');
            popup.classList.remove('active');
        });
    }

    document.addEventListener('click', (e) => {
        if(e.target === popupBg) {
            popupBg.classList.remove('active');
            popup.classList.remove('active');
        }
    });
}
function getElements(){
    let arrayCanvas = [];
    let i = 1;
    for (i;i <= 14;i++){
        let canvas = document.getElementById("canvas-" + i)
        if (canvas !== null){
            arrayCanvas.push(canvas);
        }
    }
    return arrayCanvas;
}

function drawDials(arrayCanvas){
    arrayCanvas.forEach((canvas) => {
        let bgColor = window.getComputedStyle(document.body, null).getPropertyValue('background-color');
        let fontColor = window.getComputedStyle(document.body, null).getPropertyValue('color');
        let fontFamily = window.getComputedStyle(document.body, null).getPropertyValue('font-family');

        const imgSrc = document.getElementById("arrow").getAttribute('src');

        let ctx = canvas.getContext("2d");

        // general settings
        let middleX = canvas.width / 2;
        let middleY = canvas.height / 2;
        let radius = canvas.width / 2.5 - canvas.width / 10;
        // beginning and ending of our arc. Sets by rad * pi
        let startAngleIndex = 0.75;
        let endAngleIndex = 2.25;

        // zones settings
        let zoneLineWidth = canvas.width / 20;
        // clockwise
        let counterClockwise = false;

        // ticks settings
        let tickWidth = canvas.width / 200;
        let tickColor = "#ffffff";
        let tickOffsetFromArc = canvas.width / 35;
        let digitsOffsetFromArc = canvas.width / 12;

        // Digits settings
        let canvasMin = Number(document.getElementById(canvas.id + "-min").textContent) ?? 0;
        let canvasLow = Number(document.getElementById(canvas.id + "-low").textContent) ?? 0;
        let canvasHigh = Number(document.getElementById(canvas.id + "-high").textContent) ?? 0;
        let canvasMax = Number(document.getElementById(canvas.id + "-max").textContent) ?? 0;
        let canvasResult = Number(document.getElementById(canvas.id + "-value").textContent) ?? 0;

        let zonesminlow = ((canvasLow / canvasMax * 100) * 75) / 100;
        let zoneslowhigh = (((canvasHigh - canvasLow) / canvasMax * 100) * 75) / 100;
        let zoneshighmax = (((canvasMax - canvasHigh) / canvasMax * 100) * 75) / 100;

        let blackZonesCount = ((2 * Math.PI * (25 / 100)));
        let greenZonesCount = ((2 * Math.PI * (zonesminlow / 100)));
        let yellowZonesCount = ((2 * Math.PI * (zoneslowhigh / 100)));
        let redZonesCount = ((2 * Math.PI * (zoneshighmax / 100)));

        let startAngle = (startAngleIndex - 0.50) * Math.PI;
        let endBlackAngle = startAngleIndex * Math.PI;
        let endGreenAngle = endBlackAngle + greenZonesCount;
        let endYellowAngle = endGreenAngle + yellowZonesCount;
        let endRedAngle = endYellowAngle + redZonesCount;

        let sectionOptions = [
            {
                endBlackAngle : endBlackAngle,
                startAngle: startAngle,
                endAngle : endBlackAngle,
                digit : canvasMin,
                color: "#16213b"
            },
            {
                endGreenAngle : endGreenAngle,
                startAngle: endBlackAngle,
                endAngle: endGreenAngle,
                digit : canvasLow,
                color: "#86d498"
            },
            {
                endGreenAngle : endGreenAngle,
                startAngle: endGreenAngle,
                endAngle: endYellowAngle,
                digit : canvasHigh,
                color: "#ec944a"
            },
            {
                endRedAngle : endRedAngle,
                startAngle: endYellowAngle,
                endAngle: endRedAngle,
                digit : canvasMax,
                color: "#d43445"
            }
        ];

        let DrawZones = function (startAngle,sectionOptions) {
            sectionOptions.forEach((options) => {
                ctx.beginPath();
                ctx.arc(middleX, middleY, radius, options.startAngle, options.endAngle, counterClockwise);
                ctx.lineWidth = zoneLineWidth;
                ctx.strokeStyle = options.color;
                ctx.lineCap = "butt";
                ctx.stroke();
            });
        }

        let DrawTick = function (sectionOptions) {
            sectionOptions.forEach((options) => {
                let fromX = middleX + (radius - tickOffsetFromArc) * Math.cos(options.endAngle);
                let fromY = middleY + (radius - tickOffsetFromArc) * Math.sin(options.endAngle);
                let toX = middleX + (radius + tickOffsetFromArc) * Math.cos(options.endAngle);
                let toY = middleY + (radius + tickOffsetFromArc) * Math.sin(options.endAngle);

                ctx.beginPath();
                ctx.moveTo(fromX, fromY);
                ctx.lineTo(toX, toY);
                ctx.lineWidth = tickWidth;
                ctx.lineCap = "round";
                ctx.strokeStyle = tickColor;
                ctx.stroke();
            })
        };

        let DrawText = function (sectionOptions) {
            sectionOptions.forEach((options) => {
                let x = middleX + (radius + digitsOffsetFromArc) * Math.cos(options.endAngle);
                let y = middleY + (radius + digitsOffsetFromArc) * Math.sin(options.endAngle);

                ctx.font = "10px " + fontFamily;
                ctx.fillStyle = fontColor;
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillText(options.digit, x, y);
            });
        }

        let DrawProgress = function (canvasResult) {
            let angleIndex = 0.5;
            let angle = angleIndex * Math.PI;

            let x = middleX + (radius + digitsOffsetFromArc) * Math.cos(angle);
            let y = middleY + (radius + digitsOffsetFromArc) * Math.sin(angle);

            let textProgressColor = fontColor;

            if (canvasResult >= canvasMin && canvasResult <= canvasLow){
                textProgressColor = "#86d498"
            }
            if (canvasResult >= canvasLow && canvasResult <= canvasHigh){
                textProgressColor = "#ec944a"
            }
            if (canvasResult >= canvasHigh && canvasResult <= canvasMax || canvasResult > canvasMax){
                textProgressColor = "#d43445"
            }

            ctx.font = "bold 14px " + fontFamily;
            ctx.fillStyle = textProgressColor;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";

            let textProgress = canvasResult + " units";
            ctx.fillText(textProgress, x, y - 25);

        }
        let pattern;
        let DrawArrow = function (canvasResult,canvasMax) {
            // Set the pointer to max if the result is greater than the max number
            if (parseInt(canvasResult) >= parseInt(canvasMax) )
            {
                canvasResult = canvasMax;
            }
            let startStr = -0.25 * Math.PI;
            let step = 1.5 * (((canvasResult / canvasMax ) * 100)/100);

            let img = new Image();
            img.src = imgSrc;
            img.onload = () => {
                 ctx.translate(middleX, middleY);
                 ctx.rotate(startStr + (step * Math.PI));
                 ctx.drawImage(img,-90, -6);
                 ctx.globalCompositeOperation = "source-atop";
                 pattern = ctx.createPattern(img, "no-repeat");
            }
        }

        DrawZones(startAngle,sectionOptions);
        DrawTick(sectionOptions);
        DrawText(sectionOptions);
        DrawProgress(canvasResult);
        DrawArrow(canvasResult,canvasMax);
        ctx.strokeStyle = pattern;
    })
}






