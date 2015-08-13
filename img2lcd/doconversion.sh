#!/bin/bash
LOCKDIR=/tmp/img2lcd
PERL_BIN=/usr/bin/perl
CONVERT_SCRIPT=img2lcd.pl
INPUT_PATH=queue
OUTPUT_PATH=converted

# remove lockfile in case of exit
trap " [ -f $LOCKDIR ] && rm -rf $LOCKDIR" 0 1 2 3 13 15 

# create lockdir
if ( mkdir ${LOCKDIR} ) 2> /dev/null; then
  for FILE in $INPUT_PATH/*
  do
    if [ -f "$FILE" ]
    then
      # split filename
      PARAMS=(${FILE//_/ })

      # check filename
      if [ -z "${PARAMS[0]}" ] && [ "${PARAMS[0]+xxx}" = "xxx" ]
      then
        echo "could not parse filename"
        continue
      fi

      # check bits
      if [ -z "${PARAMS[1]}" ] && [ "${PARAMS[1]+xxx}" = "xxx" ]
      then
        BITS=
      else
        BITS="-b ${PARAMS[1]}"
      fi

      # generate header
      if [ -z "${PARAMS[2]}" ] && [ "${PARAMS[2]+xxx}" = "xxx" ] || [ "${PARAMS[2]}" == "0" ]
      then
        CODE=
      else
        CODE="--code"
      fi

      OUTPUT_FILENAME=$(basename ${PARAMS[0]})
      OUTPUT_DIRNAME=$OUTPUT_PATH/$OUTPUT_FILENAME

      mkdir -p $OUTPUT_DIRNAME

      OUTPUT_FILE=$OUTPUT_DIRNAME/$OUTPUT_FILENAME".lcd"

      $PERL_BIN $CONVERT_SCRIPT $BITS $CODE $FILE $OUTPUT_FILE

      # check if output file exists
      if [ -f "$OUTPUT_FILE" ]
      then
        # also create a thumbnail
        convert -quality 7 -thumbnail 130 $FILE $OUTPUT_DIRNAME/thumb.jpg
        mv $FILE $OUTPUT_DIRNAME
      else
        echo "error: $FILE"
      fi
    fi
  done

  # remove lock dir
  rm -rf $LOCKDIR
fi
