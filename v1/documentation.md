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

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| guild_id*    | Discord Guild id                                        | true          |               |

#### POST /guild

Updates the Guild configuration according to the parameters given.

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| guild_id*    | Discord Guild id                                        | true          |               |
| channels     | Discord Channel(s) to use for Giveaways                 | false         | inherited     |
| access_roles | Discord Role(s) to use for allowing access to GIFTron   | false         | inherited     |
| strict       | Restrict access to only Access Role members             | false         | inherited     |

Note: only the fields specified will be affected. If a field is omitted, it will not be changed from its current state.

## Users
#### GET /guild/users

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| guild_id*    | Discord Guild id                                        | true          |               |

## Schedule
#### GET /guild/schedule

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| guild_id*    | Discord Guild id                                        | true          |               |
| channel      | The channel to check the schedule for                   | false         |               |
| before       | Get Giveaways before this timestamp                     | false         |               |
| after        | Get Giveaways after this timestamp                      | false         |               |
| limit        | Return up to this many Giveaways                        | false         | 50            |

### Query Giveaway
#### GET /guild/schedule/giveaway

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| giveaway_id* | GIFTron Giveaway id                                     | true          |               |

### Create Giveaway
#### POST /guild/schedule/giveaway

Schedules a Giveaway for the specified Guild

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| guild_id*    | Discord Guild id this Giveaway is being scheduled for   | true          |               |
| start        | The start time of the Giveaway in Unix time             | true          |               |
| end          | The end time of the Giveaway in Unix time               | true          |               |
| name         | The display name of the Giveaway                        | true          |               |
| visible      | Whether or not to display the game being given away     | false         | true          |
| recurring    | Whether or not this Giveaway should repeat indefinitely | false         | false         |
| key          | The key of the game you want to give away               | false         |               |
| game_id      | The id of the game you want to give away                | false         |               |

Giveaways require either a `key` to give away, or the `game_id` of the game you would like GIFTron to purchase for you at that time. Neglecting to input one of either will result in an error, due to not having anything to give away.

Giveaways may be configured as either one-time or recurring; i.e. every time at a given interval, indefinitely. When this is set, the `end` of this Giveaway is set to the `start` of the next Giveaway, which is scheduled automatically... So-on, and so-forth.

## Wallet

# Order
## Fill

# Event
An Event is a way to trigger a Giveaway, without knowing when it takes place.

# Shard

# Settings
Settings for the API are for NERDev staff only. These endpoints are not available to the general public.
## Credentials
#### POST /settings/credentials
This endpoint is for updating the credentials of the servers, in the event that one of the fields has changed.
Note that this endpoint does not authenticate using Discord.

| Field        | Description                                             | Required      | Default       |
| ---          | ---                                                     | ---           | ---           |
| clientId     | The ID of the GIFTron Discord application               | false         | inherited     |
| clientSecret | The Secret Key of the GIFTron Discord application       | false         | inherited     |
| botToken     | The Token of the GIFTron Bot, under GIFTron             | false         | inherited     |

## Whitelist
#### GET /settings/whitelist
This will lookup the current IP of each of our core servers and add them to the list of addresses considered part of our infrastructure.