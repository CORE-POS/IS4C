var customerURL = "";
var customerWindow = null;

function setCustomerURL(url)
{
    customerURL = url;
}

function launchCustomerDisplay()
{
    customerWindow = window.open(customerURL, 'Customer_Display');
}

function updateCustomerDisplay(identifier, content)
{
    if (!$.isWindow(customerWindow)) {
        console.log('Opening window...');
        launchCustomerDisplay();
    }
    childWindow.$(identifier).html(content);
}

function reloadCustomerDisplay()
{
    if (!$.isWindow(customerWindow)) {
        launchCustomerDisplay();
    }
    customerWindow.location.reload();
}

