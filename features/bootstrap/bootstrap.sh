#!/bin/bash

if [ -n "$1" ] && [ -z "$2" ]; then
    for i in {1..5}; do
        $0 $1 $i
    done

    exit 0
fi

USER="testing$2"
PASSWD="testing$2"
DIR=$(dirname $(readlink -f $0))

case "$1" in
    configure)
        sudo adduser --disabled-password --gecos "" --home /home/$USER $USER || exit 1
        echo "$USER:$PASSWD" | sudo chpasswd || exit 1
        umask 077 || exit 1
        test -d /home/$USER/.ssh || sh -c 'sudo mkdir -p /home/$USER/.ssh || exit 1'
        cat >> app/config/.ssh/test/id_rsa$2 < $DIR/id_rsa || exit 1
        sudo sh -c "< $DIR/id_rsa.pub cat >> /home/$USER/.ssh/authorized_keys" || exit 1
        sudo chown -R $USER:$USER /home/$USER/.ssh/ && sudo chmod -R 700 /home/$USER/.ssh/ || exit 1
    ;;

    clean)
        # The crontab needs to be clean manually before the user is deleted
        if [ `sudo crontab -u $USER -l 2>/dev/null | wc -l` -gt 0 ]; then
            sudo crontab -u $USER -r || exit 1
        fi

        if [ `grep "$USER" /etc/passwd | wc -l` -eq 1 ]; then
            sudo deluser $USER || exit 1
        fi

        if [ -d /home/$USER/ ]; then
            sudo rm -Rf /home/$USER/ || exit 1
        fi
    ;;

    test)
        chmod 600 $DIR/id_rsa $DIR/id_rsa.pub
        ssh -o PasswordAuthentication=no -o KbdInteractiveAuthentication=no \
            -o ChallengeResponseAuthentication=no \
            -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no \
            -i $DIR/id_rsa 2>/dev/null \
            $USER@localhost "ls /home/$USER 1>/dev/null 2>&1 && echo '[OK]'" || sh -c "echo '[KO]' && exit 1"
    ;;

    *)
        echo "Usage: $0 [configure|test|clean]"
    ;;
esac
