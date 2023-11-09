#!/usr/bin/python
from selenium import webdriver
from selenium.common.exceptions import ElementNotInteractableException
import time
import sys
import os
import os.path
import traceback
import getopt
import datetime

TMP_DIR = '/tmp/un'
SITE_LOGIN = ''
SITE_PASSWD = ''
ACCOUNT = ''

#############################################
# Handle CLI arguments
#############################################
def usage():
    print("Usage: myunfi2.py --user=USER --password=PASSWORD --account=ACCOUNT\n")
    print("\t--user, -u\tUsername for MyUNFI")
    print("\t--password, -p\tPassword for MyUNFI")
    print("\t--account, -a\tAccount number")

try:
    opts, args = getopt.getopt(sys.argv[1:], "u:p:a:", ["user=", "password=", "account="])
except getopt.GetoptError as err:
    print((str(err)))
    usage()
    sys.exit(1)
for o, a in opts:
    if o in ("-u", "--user"): SITE_LOGIN=a
    elif o in ("-p", "--pass"): SITE_PASSWD=a
    elif o in ("-a", "--account"): ACCOUNT=a

if SITE_LOGIN == "" or SITE_PASSWD == "" or ACCOUNT == "":
    usage()
    sys.exit(1)

#############################################
# Initialize webdriver in headless mode
#
# Downloading files requires some extra
# setup for the driver to know where it
# should be saved
#############################################
def init_driver():
    chrome_opts = webdriver.ChromeOptions()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("window-size=1400,1000")
    chrome_opts.add_experimental_option("prefs", { "download.default_directory": TMP_DIR })
    driver = webdriver.Chrome(chrome_options=chrome_opts)

    driver.command_executor._commands["send_command"] = ("POST", '/session/$sessionId/chromium/send_command')
    params = {'cmd': 'Page.setDownloadBehavior', 'params': {'behavior': 'allow', 'downloadPath': TMP_DIR}}
    command_result = driver.execute("send_command", params)

    if not(os.path.exists(TMP_DIR)):
        os.mkdir(TMP_DIR)

    return driver

os.chdir(os.path.dirname(os.path.abspath(__file__)))
exit_code = 0

try:
    driver = init_driver()
    driver.maximize_window()
    driver.get("https://myunfi.com")
    time.sleep(2);
    driver.find_element_by_id("signInName").send_keys(SITE_LOGIN)
    driver.find_element_by_tag_name('button').click();
    time.sleep(5);
    driver.get_screenshot_as_file("login.png")

    driver.find_element_by_id("password").send_keys(SITE_PASSWD)
    driver.find_element_by_tag_name('button').click();
    time.sleep(5);

    driver.get("https://www.myunfi.com/shopping")
    time.sleep(2);
    driver.get_screenshot_as_file("shopping.png")

    today = datetime.date.today()
    for i in xrange(5):
        cur = today - datetime.timedelta(i)
        driver.get("https://www.myunfi.com/shopping/reports");
        time.sleep(5)

        driver.find_element_by_css_selector('div[data-testid="ReportCardOverlay.Root"]:nth-child(2) > div > div:nth-child(2) > div:nth-child(2) > div').click()
        time.sleep(1)
        driver.find_element_by_css_selector('input[data-testid="InputField.Input"]').send_keys(cur.__format__('%m/%d/%Y'))
        time.sleep(1)
        driver.find_element_by_id('transactionTypes').click();
        time.sleep(1)
        select_js = "document.querySelectorAll('div.MuiPopover-root')[0].querySelector('span.MuiTypography-root').click();"
        driver.execute_script(select_js)
        time.sleep(1)
        apply_js = "document.querySelectorAll('div.MuiPopover-root')[0].querySelectorAll('span.BaseButton-Label')[1].click()"
        driver.execute_script(apply_js)
        time.sleep(1)
        menu_js = "document.querySelectorAll('button[data-testid=\"undefined-menu\"]')[0].click()"
        driver.execute_script(menu_js)
        time.sleep(1)
        select_js = "document.querySelectorAll('div.MuiPopover-root')[1].querySelectorAll('li.MuiButtonBase-root')[1].click()"
        driver.execute_script(select_js)
        time.sleep(1)
        download_js = "document.querySelector('div[data-testid=\"GenerateReportDialog.Root\"]').querySelectorAll('span.BaseButton-Label')[1].click();"
        driver.execute_script(download_js);
        time.sleep(1)
        driver.get_screenshot_as_file("nextdate" + str(i) + ".png")
        dialog_js = "document.querySelectorAll('div.MuiDialog-root')[1].querySelectorAll('button')[0].click()"
        driver.execute_script(dialog_js)
        time.sleep(1)

    driver.get("https://www.myunfi.com/download-center");
    time.sleep(5)
    download_js = """
var menus = document.querySelectorAll('button[data-testid="IconMenu.Button"]');
for (var i = 0; i < menus.length; i++) {
    menus[i].click();
    document.querySelector('div#boxToClick li').click();
}
    """
    driver.execute_script(download_js)
    driver.get_screenshot_as_file('downloading.png')
    time.sleep(30)


except Exception as e:
    print(e)
    traceback.print_exc()
    exit_code = 1
    print "Getting final screenshot"
    driver.get_screenshot_as_file("error.png")

driver.quit()
sys.exit(exit_code)

