PROGRESS_FILE=/tmp/dependancy_i2cExt_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
echo 50 > ${PROGRESS_FILE}
apt-get install -y python-smbus python-requests python-pyudev i2c-tools
echo 80 > ${PROGRESS_FILE}
modprobe aml_i2c
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}