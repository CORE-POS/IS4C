#!/usr/bin/python
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
import time
import sys
import os
import getopt
import MySQLdb
import dsnparse
import hashlib

def usage():
    print("Usage: scrape.py --user=USER --password=PASSWORD --dsn=DSN URL1 [URL2] [URL3] ...")
    print("\t--user, -u\tUsername for Instacart")
    print("\t--password, -p\tPassword for Instacart")
    print("\t--dsn, -d\tDatabase connection string")
    print("\t--verbose, -v\tPrint debug info")

try:
    opts, args = getopt.getopt(sys.argv[1:], "u:p:d:v", ["user=", "password=", "dsn=", "verbose"])
except getopt.GetoptError as err:
    print((str(err)))
    usage()
    sys.exit(1)

USER=""
PASS=""
DSN=""
VERBOSE = False
for o, a in opts:
    if o in ("-u", "--user"): USER=a
    elif o in ("-p", "--pass"): PASS=a
    elif o in ("-d", "--dsn"): DSN=a
    elif o in ("-v", "--verbose"): VERBOSE=True

if USER == "" or PASS == "" or DSN == "":
    usage()
    sys.exit(1)

if len(args) == 0:
    print("At least one URL is required")
    usage()
    sys.exit(1)

try:
    dsn_parts = dsnparse.parse(DSN)
except:
    print("Invalid DSN value")
    sys.exit(1)

try:
    db_con = MySQLdb.connect(dsn_parts.host, dsn_parts.username, dsn_parts.password, dsn_parts.paths[0])
    dbc = db_con.cursor()
except:
    print("DB connection failed")
    sys.exit(1)

MY_DIR = os.path.dirname(os.path.abspath(__file__))
    
chrome_opts = webdriver.ChromeOptions()
chrome_opts.add_argument("--headless")
driver = webdriver.Chrome(chrome_options=chrome_opts)
driver.get("https://www.instacart.com/")
if VERBOSE: print("Loading site")
time.sleep(3)
#driver.get_screenshot_as_file("one.png")

driver.find_element_by_class_name("login-link").click()
driver.find_element_by_name("email").send_keys(USER)
driver.find_element_by_name("password").send_keys(PASS + Keys.RETURN)
if VERBOSE: print("Logging in")
time.sleep(5)
#driver.get_screenshot_as_file("two.png")

for url in args:

    if VERBOSE: print(("Getting item " + url))
    driver.get(url)
    time.sleep(2)
    md5 = hashlib.md5()
    md5.update(url)
    filename = md5.hexdigest()
    driver.get_screenshot_as_file(MY_DIR + "/images/" + filename + ".png")
    elem = driver.find_element_by_class_name("item-price")
    output = str(elem.text)
    price = 0.0
    sale_price = 0.0
    if output.endswith(" each"):
        # output ending with each are usually per-lb items
        # try to find the per-lb price but use the each
        # price if that doesn't work
        output = output.replace(" each", "")
        price = float(output.strip().replace("$", ""))
        try:
            weight_elem = driver.find_element_by_xpath("//div[@class='item-price']/following-sibling::div")
            perlb = weight_elem.text.replace(" per lb", "")
            price = float(perlb.strip().replace("$", ""))
        except:
            pass
    elif "\n" in output:
        # newline usually means the item is on sale
        # use sale price and $x.xx off info to construct
        # regular price
        pts = output.split("\n")
        pts[0] = pts[0].strip().replace("$", "")
        pts[1] = pts[1].strip().replace("$", "").replace(" off", "");
        sale_price = float(pts[0])
        price = sale_price + float(pts[1])
    else:
        price = float(output.strip().replace("$", ""))

    if (VERBOSE):
        print(("Price: " + str(price)))
        print(("Sale:" + str(sale_price)))

    try:
        dbc.execute("UPDATE InstaCompares SET price=%s, salePrice=%s, modified=NOW() WHERE url=%s", (price, sale_price, url))
        db_con.commit()
    except:
        if (VERBOSE): print("SQL FAIL")
        pass

    print((output + ":::" + url))

driver.quit()
if (VERBOSE): print("Closing down")

