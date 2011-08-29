#!/usr/bin/python

import math

class fraction:
    error = 6
    
    def __init__(self,w=0,n=0,d=1):
        self.numerator = n
        self.denominator = d
        self.whole = w
    
    def parseDecimal(self,decimal):
       (f,w) = math.modf(decimal) 
       self.whole = int(w)

       n = 1
       d = 1
       f = round(f,self.error)
       error = 1 * (10 ** (-1*self.error))
       while round(math.fabs(float(self.numerator)/self.denominator - f),self.error) > error:
           self.numerator = 0
           self.denominator = 1
           d += 1
           frac = fraction(0,n,d)
           while round(float(self.numerator)/self.denominator + float(n)/d,self.error) <= f + error:
               self.add(frac)
               
           if d > 100:
               print "APPROXIMATION ERROR"
               break;
               
       print self.toStr()

           
    def add(self,frac):
        if self.denominator == frac.denominator:
            self.numerator += frac.numerator
        else:
            self.numerator = self.numerator*frac.denominator + frac.numerator*self.denominator
            self.denominator = self.denominator*frac.denominator
        
    def reduce(self):
        for i in range(self.numerator,0,-1):
            if self.numerator % i == 0 and self.denominator % i == 0:
                self.numerator /= i
                self.denominator /= i
                break
        
    def toStr(self):
        return str(self.whole) + " " + str(self.numerator) + "/" + str(self.denominator)
        