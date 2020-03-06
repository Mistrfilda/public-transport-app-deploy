# Unifi local dns 

Simple DNS config for unifi USG.

- Get controller IP address - login into controller in browser
- SSH into controller - propably with login ubnt and password (password for devices) 

```bash
ssh ubnt@192.168.1.178
```

- Navigate to /srv/unifi/data/sites/default
```bash
cd /srv/unifi/data/sites/default
```
- Create file config.gateway.json (it is also possible to conect via Forklitf or any other sftp client and use sublime instead of freaking VI :D)
- Put this config
```json
{
"system": {
  "static-host-mapping": {
   "host-name": {
    "filda-pid.cz": {
     "alias": [
      "fildapidcz"
     ],
     "inet": [
      "192.168.1.183"
     ]
    },
    "fildapid.cz": {
     "alias": [
      "fildapidcz2"
     ],
     "inet": [
      "192.168.1.183"
     ]
    },
    "fildapidik.cz": {
     "alias": [
      "fildapidcz3"
     ],
     "inet": [
      "192.168.1.183"
     ]
    }
   }
  }
 }
}
```
- Save and change file owner to unifi user
```bash
chown unifi:unifi config.gateway.json
```

- Go to controller gui and on devices settings click on usg, then navigate to settings and click `Provision`

- Wait till is done and test it by pinging
```bash
ping fildapid.cz
``` 