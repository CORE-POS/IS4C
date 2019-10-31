#!/usr/bin/python
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.action_chains import ActionChains
import time
import sys
import os
import os.path
import getopt

def usage():
    print("Usage: usf.py --user=USER --password=PASSWORD")
    print("\t--user, -u\tUsername for US Foods")
    print("\t--password, -p\tPassword for US Foods")
    print("\t--verbose, -v\tPrint debug info")

try:
    opts, args = getopt.getopt(sys.argv[1:], "u:p:v", ["user=", "password=", "verbose"])
except getopt.GetoptError as err:
    print((str(err)))
    usage()
    sys.exit(1)

USER=""
PASS=""
VERBOSE = False
for o, a in opts:
    if o in ("-u", "--user"): USER=a
    elif o in ("-p", "--pass"): PASS=a
    elif o in ("-v", "--verbose"): VERBOSE=True

if USER == "" or PASS == "":
    usage()
    sys.exit(1)

if not(os.path.exists("/tmp/usf")):
    os.mkdir("/tmp/usf")
if os.path.exists("/tmp/usf/invoiceDetails.ZIP"):
    os.unlink("/tmp/usf/invoiceDetails.ZIP")

numFiles = len(os.listdir("/tmp/usf"))

chrome_opts = webdriver.ChromeOptions()
chrome_opts.add_argument("--headless")
driver = webdriver.Chrome(chrome_options=chrome_opts)
driver.command_executor._commands["send_command"] = ("POST", '/session/$sessionId/chromium/send_command')
params = {'cmd': 'Page.setDownloadBehavior', 'params': {'behavior': 'allow', 'downloadPath': '/tmp/usf'}}
command_result = driver.execute("send_command", params)

# Load the login page
if VERBOSE: print("Init")
driver.get("https://www3.usfoods.com/order/")
time.sleep(3)

# Post credentials
if VERBOSE: print("Login")
driver.find_element_by_name("it9").send_keys(USER)
driver.find_element_by_name("it1").send_keys(PASS)
driver.find_element_by_id('cb1').click();
time.sleep(3)

# Navigate to the invoices page
# The hover stuff is required to generate the
# menu clickable elements
if VERBOSE: print("Nav")
mainMenu = driver.find_element_by_id("dgfSPT:pt_i7:2:pt_s46:pt_gl1")
hover = ActionChains(driver).move_to_element(mainMenu)
hover.perform()
time.sleep(1)
subMenu = driver.find_element_by_id("dgfSPT:pt_i7:2:pt_s46:pt_i5:1:pt_cl6111")
hover = ActionChains(driver).move_to_element(subMenu)
hover.perform()
time.sleep(1)
subMenu.click()
time.sleep(3)

# Click one more link to download invoices
if VERBOSE: print("More Nav")
driver.find_element_by_id("pt1:lv1:0:cil2::icon").click()
time.sleep(3)

# Download the invoices
#
# I'm using javscript to check the boxes because I cannot
# figure out what element to click on in the UI that will
# carry through to the underlying checkbox
js = """
for (var elem of document.getElementsByTagName('input')) {
    if (elem.type == 'checkbox') {
        elem.checked = true;
    }
}
"""
driver.execute_script(js)
funkySelect = driver.find_element_by_class_name("jqTransformSelectWrapper")
funkySelect.find_element_by_xpath("div/a").click()
time.sleep(1)
funkySelect.find_element_by_xpath("ul/li[4]").click()
time.sleep(1)
driver.find_element_by_id("r1:0:pt1:cb2").click()
#driver.get_screenshot_as_file("one.png")
#time.sleep(1)
#driver.get_screenshot_as_file("two.png")
if VERBOSE: print("Downloading")
count=0
while (True):
    time.sleep(1)
    if os.path.exists("/tmp/usf/invoiceDetails.ZIP"):
        break
    count += 1
    if count > 99:
        break

driver.quit()
sys.exit(0)

