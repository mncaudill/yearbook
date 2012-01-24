yearbook snippets
===================

I decided to take all the blog posts, Twitter messages, and Flickr images I made this year, combine them, typeset them, and then get it printed
in a hard-bound book. I wrote a bit about the reasoning [here](http://nolancaudill.com/2011/11/29/concrete-words/).

There was a lot of poking a pawing at the scripts I used to create the final product so I thought I'd share them in case someone else could get some use out of them.

Big warning: these are mostly worthless until you change them to fit your project. While all the code here works, and it ended up giving me a decent-looking book, you'll need to modify it, which is mostly the point. This is *your* retrospective and thus shouldn't be a cookie-cutter running of the code I wrote (if that would even work).

I'll now explain a bit about the pieces:

## The Blog Posts

All my blog posts are just flat HTML (via jekyll) so getting my blog onto my PC was already done. You'll probably need to run some magic incantation of `wget` or `curl` to get yours if they're hosted somewhere else.

TeX, specifically pdflatex, was the workhorse on typesetting it so I needed to get these HTML files into tex format. I ran a `find . -name "*html" | xargs -I{} python texify.py {}` in my jekyll's site directory which then ran each of the files through [pandoc](http://johnmacfarlane.net/pandoc/). Pandoc is a super magic text transformation library that will slurp in most text format and then spit out a transformed version. In this case, I was reading HTML and spitting out .tex files. You can see the command in `texify.py`.

After I had all these converted tex files, I actually loaded all my files up in vim, made a macro that cleaned out things like header and footer, and then just ran the macro across all the open files. I forgot this magic spell almost as soon as I did it, but `bufdo` sounds familiar. I'd google something like "vim macro across all open buffers" or something.

Now that I have a directory full of tex files, one file per blog post, you need a master tex file that actually describes the full document, as well as the pointers to all the various tex files to include. This is the `book.tex` file in this repository. This is mine lifted as-is, so this is what the finished result looks like and should give you a good idea of how to put yours together. 

TeX is a frustratingly arcane markup language, but it is extremely powerful and can create beautiful documents. It's worth it, trust me.

I've also included a sample blog post tex file. This post includes a couple of images by `\includegraphics` to give you a heads start on that.

## Twitter

To format your Twitter posts, you first need the actual Twitter messages. This is actually hard, if not impossible, if you're especially prolific.

Twitter famously only allows you to fetch your last 3200 messages. This limit is enforced but on the official website and by the API. 

I've been running [tweetnest](http://pongsocket.com/tweetnest/) on my server for a year or so, mainly because I think it's pretty, but it turned out to do a whizbang job of archiving as well. Surprise, surprise: this was the source of Twitter messages for my book. I just dumped the table to a text file (via `mysqldump`) and used that as my source file.

Inside of `twitter/tweet_transform.php`, you'll see the reading of this file and then spitting out the tex file, separating the messages by month and then by the day.

There are some positively Nolan-specific things in here. All the dates in Tweetnest (and probably Twitter's API) return a timestamp for each Tweet using seconds since the epoch. If I only tweeted from San Francisco in all of 2011, getting nice dates would have been easy: just set the timezone at the top of the script and then call it a day. But as it turned out, I climbed on and off airplanes at various locations and at different times. You'll see a block of code that dynamically sets the timezone according to when I was boarding and de-boarding airplanes.

Another sort of fuzzy, human thing I added to this that you may want to be aware of is that I fudged the edges of what constituted a "day". Instead of  a day being midnight to midnight, I grouped tweets on a 4am boundary. Best I could tell, I never tweeted before 4am after waking up, and never tweeted past 4am by staying up from the night before. This way a day is defined as waking up to going asleep (or passing out, some nights).

This script also runs follows some common URL shorteners so you won't see any bit.ly or goo.gl links in your permanent archive.

The hard part of getting the Twitter section together is actually getting the tweets together, but once you do that, it's a breeze.

## Flickr

I uploaded about 600 pictures to Flickr this year. I really wanted to display every single picture for the sake of completeness but figuring out a way to that visually was difficult.

I ended up going something like [Google's image search](http://images.google.com/search?q=kitten&hl=en&site=webhp&tbm=isch). Stephen Woods was also a major source of inspiration for the layout. This layout lets you plop a lot of images on a page and letting them use their natural dimensions to shoulder out more space as needed.

Instead of forcing tex to layout individual images, or individual rows, I figured it would be easier to create an image that represented the full page and then put that on the page, not unlike the old days of people adding `<area>` tags to full-page images in the early days of the web.

The `flickr/justified.php` file is what creates these image files and then the `flickr.tex` file that includes them all. 

I used Aaron Cope's [parallel-flickr](http://straup.github.com/parallel-flickr/) as the source of the images. This project conveniently creates an easy-to-query database so I could do something like "give me all the images from Jan 1, 2011, to Dec 31, 2011 ordered by date_taken ascending". I used the output of this query to select the appropriate images in the correct order and rsynced them to my book's Flickr directory.

There are a few fuzzy parameters that let you things like a maximum row height, and how wide your rows are. Feel free to twiddle these knobs as you see fit.

## Conclusion

Nothing about this is drop-in-and-run but there are a lot of gotchas that I came across that might help someone else if they ever decide to tackle a project like this.
