touch /tmp/dependancy_i2cExt_in_progress
echo 0 > /tmp/dependancy_i2cExt_in_progress
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
modprobe aml_i2c 
modprobe i2c_dev
adduser www-data i2c
echo "i2c-dev" | sudo tee /etc/modules
echo "aml_i2c" | sudo tee /etc/modules
echo 30 > /tmp/dependancy_i2cExt_in_progress
apt-get update
echo 50 > /tmp/dependancy_i2cExt_in_progress
apt-get install -y python-smbus python-requests python-pyudev
echo 100 > /tmp/dependancy_i2cExt_in_progress
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm /tmp/dependancy_i2cExt_in_progress