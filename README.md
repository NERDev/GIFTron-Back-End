
# GIFTron API
### Disclaimer
This documentation is only intended for internal use, and to provide operational transparency. No attempt is being made to support third-party deployments or implementation of this application, nor is NERDev responsible for accidental changes to production data.

# User
#### GET /user?{user.id}
The User object represents the currently-logged-in user as it appears in our system.

## Guilds
#### GET /user/guilds?{user.id}
This returns a list of guilds that relate to both the user, and to our system. The formatting of the returned guilds is identical to Discord's [Partial Guild Objects](https://discordapp.com/developers/docs/resources/user#get-current-user-guilds "Discord Documentation").

## Auth
#### GET /user/auth
This returns a Discord OAuth2 URL with which the user may use to authenticate

# Guild

## Info
## Configure
## Users
## Schedule
## Wallet