#!/bin/sh
# printKick.sh
# 14Apr12 EL Print something and open cash drawer via Epson TM88 printer.

# Refer to: Epson Technical Guide "Opening the drawer kickout"
# https://docs.google.com/a/westendfood.coop/file/d/1Q1QhwLa_2ujCBfL-ikLrO10hYMkCf_Jqjy27G6O0Nl-m6dkN1ln-5Kr11v-U/edit

# --VARIALBLES and --CONSTANTS - - - - - - - - - - - - - - - - -

# Scroll printer paper so you can see the message.
SCROLL=\n\n\n\n\n\n\n\n
PRINTER=/dev/lp0

# Use pin 2
P2=0
# Use pin 5
# Does not work on the APG S100 BL1616 with serial connector.
P5=1
#
# Pulse control.
# I don't know the significance of the values.
# These "work". I haven't tried others.
# Pulse duration.
# ASCII value of char * 2ms
T1=y
# Wait duration (before next pulse).
# ASCII value of char * 2ms
T2=z

# --MAIN - - - - - - - - - - - - - - - - - - - - - - - - - - - -

printf "Open sesame!" > $PRINTER

printf "\x1Bp${P2}${T1}${T2}" > $PRINTER

printf "${SCROLL}" > $PRINTER
