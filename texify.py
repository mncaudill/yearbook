import sys, os, re

filepath = sys.argv[1]
filename = re.sub(r'/', '-', filepath[9:-5])

os.system('pandoc -S -o %s.tex %s' % (filename, filepath))
