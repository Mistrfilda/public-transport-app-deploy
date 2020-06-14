# My raspberry pi 4b (4 GB)

- Rpi 4b (4 gb)
- Boot from ssd
- case - https://www.alza.cz/case-pro-raspberry-pi-4-acryl-fan-d5664789.htm
- https://www.alza.cz/axago-ee25-xa3-aline-d1775020.htm
- https://www.alza.cz/kingston-a400-120gb-7mm-d4798610.htm

# Setup
### 14.6.2020 - usb boot still in beta
1. Download new raspberry pi image from https://www.raspberrypi.org/downloads/raspberry-pi-desktop/
2. Plug in ssd and any sd card into pc
3. Use some program to burn image to both disks - I used etcher https://www.balena.io/etcher/
4. Plug in sd card into rpi
5. Use this commands, to update rpi eeprom
```bash
sudo apt-get update
sudo apt-get upgrade -y
sudo rpi-update 
sudo reboot
```

```bash
sudo apt install rpi-eeprom -y
sudo nano /etc/default/rpi-eeprom-update
```

Change "critical" to "beta"

Next step is to manually update eeprom - check latest version here https://github.com/raspberrypi/rpi-eeprom/tree/master/firmware/beta
```bash
sudo rpi-eeprom-update -d -f /lib/firmware/raspberrypi/bootloader/beta/pieeprom-2020-05-15.bin
sudo reboot
```

Check if everything updated successfully
```bash
vcgencmd bootloader_version 
vcgencmd bootloader_config
```
The second command will show us what boot order we are using. We are looking for 0xf41 as the BOOT_ORDER value.
   
Continue by plugging in ssd with burned raspbian and then

```bash
sudo mkdir /mnt/usb
sudo mount /dev/sda1 /mnt/usb
sudo cp /boot/*.elf /mnt/usb
sudo cp /boot/*.dat /mnt/usb
```   

Shutdown, disconect sd card from rpi and try to boot from ssd :)