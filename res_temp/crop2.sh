#!/bin/bash
cd "/Users/akilamendis/PROJECTS/Ones n Zeros/YT Astro/Magulsakwala-tv/res_temp"

names1=(mars moon sun mercury jupiter ketu venus saturn rahu)
ys1=(88 144 200 256 312 368 424 480 536)
for i in "${!names1[@]}"; do
  magick images.jpeg -crop 76x52+5+${ys1[$i]} +repage "crops/set1_${names1[$i]}.png"
done

names2=(sun moon mars mercury jupiter venus saturn rahu ketu)
ys2=(132 183 234 285 336 387 438 489 540)
for i in "${!names2[@]}"; do
  magick images-2.jpeg -crop 76x48+5+${ys2[$i]} +repage "crops/set2_${names2[$i]}.png"
done
