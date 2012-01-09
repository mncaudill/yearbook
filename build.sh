#!/bin/bash

find -name "*aux" | xargs rm
pdflatex book.tex && pdflatex book.tex
