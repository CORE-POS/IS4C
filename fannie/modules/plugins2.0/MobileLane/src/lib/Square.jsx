import MobileDetect from 'mobile-detect';

var md = new MobileDetect(window.navigator.userAgent);

function isAndroid() {
    return md.is('AndroidOS'); 
}

function isIOS() {
    return md.is("iOS") || md.is("iPadOS");
}

function androidPay(amount, clientID, urlBase) {
    var tenderTypes =
        "com.squareup.pos.TENDER_CARD,com.squareup.pos.TENDER_CARD_ON_FILE,com.squareup.pos.TENDER_CASH,com.squareup.pos.TENDER_OTHER";
    var url = "intent:#Intent;" +
        "action=com.squareup.pos.action.CHARGE;" +
        "package=com.squareup;" +
        "S.com.squareup.pos.WEB_CALLBACK_URI=" + urlBase + "SquareCallback.php" + ";" +
        "S.com.squareup.pos.CLIENT_ID=" + clientID + ";" +
        "S.com.squareup.pos.API_VERSION=v2.0;" +
        "i.com.squareup.pos.TOTAL_AMOUNT=" + round(amount * 100) + ";" +
        "S.com.squareup.pos.CURRENCY_CODE=USD;" +
        "S.com.squareup.pos.TENDER_TYPES=" + tenderTypes + ";" +
        "end";

    window.open(url);
}

function iosPay(amount, clientID, urlBase) {
    var dataParameter = {
        amount_money: {
            amount: round(amount * 100),
            currency_code: "USD"
        },
        callback_url: urlBase + "SquareCallback.php",
        client_id: clientID,
        version: "1.3",
        options: {
            supported_tender_types: ["CREDIT_CARD","CASH","OTHER","SQUARE_GIFT_CARD","CARD_ON_FILE"]
        }
    };
    var url = "square-commerce-v1://payment/create?data=" +
        encodeURIComponent(JSON.stringify(dataParameter));

    window.location = url;
}

export function pay(amount, clientID, urlBase) {
    if (isAndroid()) {
        androidPay(amount, clientID, urlBase);
    } else if (isIOS()) {
        iosPay(amount, clientID, urlBase);
    }
}

export function available() {
    return isAndroid() || isIOS();
}


