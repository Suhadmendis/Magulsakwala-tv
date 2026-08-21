#!/bin/bash
cd "/Users/akilamendis/PROJECTS/Ones n Zeros/YT Astro/Magulsakwala-tv/res_temp"
mkdir -p crops

names1=(mars moon sun mercury jupiter ketu venus saturn rahu)
ys1=(85 141 198 254 310 366 423 479 533)
for i in "${!names1[@]}"; do
  magick images.jpeg -crop 78x66+4+${ys1[$i]} +repage "crops/set1_${names1[$i]}.png"
done

names2=(sun moon mars mercury jupiter venus saturn rahu ketu)
ys2=(130 181 232 284 335 386 437 489 538)
for i in "${!names2[@]}"; do
  magick images-2.jpeg -crop 78x66+4+${ys2[$i]} +repage "crops/set2_${names2[$i]}.png"
done
