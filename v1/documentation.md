
# GIFTron API
### Disclaimer
This documentation is only intended for internal use, and to provide operational transparency. No attempt is being made to support third-party deployments or implementation of this application, nor is NERDev responsible for accidental changes to production data.

#### Introduction
stuff

# User
#### GET /user
The User object represents the currently-logged-in user as it appears in our system.

## Guilds
#### GET /user/guilds
This returns a list of guilds that relate to both the user, and to our system. Similar in usage to Discord's [Get Current User Guilds](https://discordapp.com/developers/docs/resources/user#get-current-user-guilds "Discord Documentation") endpoint.

## Auth
#### GET /user/auth
This returns a Discord OAuth2 URL with which the user may use to authenticate.


# Guild
#### GET /guild/?{guild.id}
The Guild object represents the guild specified by its `id` in the querystring as it appears in our system.
## Configure
#### POST /guild/configure/?{guild.id}

## Users
#### GET /guild/users/?{guild.id}

## Schedule
#### GET /guild/schedule/?{guild.id}

### Query Giveaway
#### GET /guild/schedule/giveaway/?{giveaway.id}

### Create Giveaway
#### POST /guild/schedule/giveaway/?{guild.id}


## Wallet

# Order
## Fill

# Shard

# 