#!/bin/bash

# runs via window manager on startup
/usr/bin/firefox "http://stuff.mediamead.org/feed" &

# better fix startup move
while ! xdotool search --name Mozilla; do 
  echo "waiting"; 
  sleep 5; 
done;

sleep 1;

wmctrl -a Mozilla -b remove,maximized_vert,maximized_horz; #unmaximize
wmctrl -a Mozilla -e "0,0,0,768,768"; #move to the right desktop
wmctrl -a Mozilla -b add,maximized_vert,maximized_horz; #maximize
xdotool key --window `xdotool search --name Mozilla` F11; #send F11 (fullscreen)

# no more used
# bash -c "LANG=ru_RU.UTF-8 urxvt -geometry 102x25 -e /bin/bash --rcfile /etc/profile.UTF-8 /home/diod/aura-made/runme.sh" &

