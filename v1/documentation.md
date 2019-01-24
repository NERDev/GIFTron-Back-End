# GIFTron API
### Disclaimer
This documentation is only intended for internal use, and to provide operational transparency. No attempt is being made to support third-party deployments or implementation of this application, nor is NERDev responsible for accidental changes to production data.

**USE AT YOUR OWN RISK**

### Introduction
stuff

# User
#### GET /user
The User object represents the currently-logged-in user as it appears in our system.

## Guilds
#### GET /user/guilds
This returns a list of Guilds that relate to both the user, and to our system. Similar in usage to Discord's [Get Current User Guilds](https://discordapp.com/developers/docs/resources/user#get-current-user-guilds "Discord Documentation") endpoint.

## Auth
#### GET /user/auth
This returns a Discord OAuth2 URL with which the user may use to authenticate.


# Guild
#### GET /guild
The Guild object represents the Guild specified by its `id` in the querystring as it appears in our system.

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| guild_id*    | Discord Guild id                                      | true          |               |

#### POST /guild

Updates the Guild configuration according to the parameters given.

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| guild_id*    | Discord Guild id                                      | true          |               |
| channels     | Discord Channel(s) to use for Giveaways               | false         | inherited     |
| access_roles | Discord Role(s) to use for allowing access to GIFTron | false         | inherited     |
| strict       | Restrict access to only Access Role members           | false         | inherited     |

Note: only the fields specified will be affected. If a field is omitted, it will not be changed from its current state.

## Users
#### GET /guild/users

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| guild_id*    | Discord Guild id                                      | true          |               |

## Schedule
#### GET /guild/schedule

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| guild_id*    | Discord Guild id                                      | true          |               |
| channel      | The channel to check the schedule for                 | false         |               |
| before       | Get Giveaways before this timestamp                   | false         |               |
| after        | Get Giveaways after this timestamp                    | false         |               |
| limit        | Return up to this many Giveaways                      | false         | 50            |

### Query Giveaway
#### GET /guild/schedule/giveaway

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| giveaway_id* | GIFTron Giveaway id                                   | true          |               |

### Create Giveaway
#### POST /guild/schedule/giveaway

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| guild_id*    | Discord Guild id this Giveaway is being scheduled for | true          |               |

## Wallet

# Order
## Fill

# Shard

# Settings
Settings for the API are for NERDev staff only. These endpoints are not available to the general public.
## Credentials
#### POST /settings/credentials
This endpoint is for updating the credentials of the servers, in the event that one of the fields has changed.
Note that this endpoint does not authenticate using Discord.

| Field        | Description                                           | Required      | Default       |
| ---          | ---                                                   | ---           | ---           |
| clientId     | The ID of the GIFTron Discord application             | false         | inherited     |
| clientSecret | The Secret Key of the GIFTron Discord application     | false         | inherited     |
| botToken     | The Token of the GIFTron Bot, under GIFTron           | false         | inherited     |

## Whitelist
#### GET /settings/whitelist
This will lookup the current IP of each of our core servers and add them to the list of addresses considered part of our infrastructure.