<?php

require "oauth2-lib.php";

class DiscordAPI extends OAuth2Client
{
    function __construct($id, $secret)
    {
        $params = [
            'clientId'                => $id,
            'clientSecret'            => $secret,
            'redirectUri'             => 'http://dev.nerdev.io/giftron/api/v1/login',
            'urlBase'                 => 'https://discordapp.com/api',
            'urlAuthorize'            => 'https://discordapp.com/api/oauth2/authorize',
            'urlAccessToken'          => 'https://discordapp.com/api/oauth2/token'
        ];

        $this->perms = [
            // General
            "generalCreateInstantInvite" => 0x1,
            "generalKickMembers:" => 0x2,
            "generalBanMembers:" => 0x4,
            "generalAdministrator:" => 0x8,
            "generalManageChannels:" => 0x10,
            "generalManageServer:" => 0x20,
            "generalChangeNickname:" => 0x4000000,
            "generalManageNicknames:" => 0x8000000,
            "generalManageRoles:" => 0x10000000,
            "generalManageWebhooks:" => 0x20000000,
            "generalManageEmojis:" => 0x40000000,
            "generalViewAuditLog:" => 0x80,
            // Text
            "textAddReactions:" => 0x40,
            "textReadMessages:" => 0x400,
            "textSendMessages:" => 0x800,
            "textSendTTSMessages:" => 0x1000,
            "textManageMessages:" => 0x2000,
            "textEmbedLinks:" => 0x4000,
            "textAttachFiles:" => 0x8000,
            "textReadMessageHistory:" => 0x10000,
            "textMentionEveryone:" => 0x20000,
            "textUseExternalEmojis:" => 0x40000,
            // Voice
            "voiceViewChannel:" => 0x400,
            "voiceConnect:" => 0x100000,
            "voiceSpeak:" => 0x200000,
            "voiceMuteMembers:" => 0x400000,
            "voiceDeafenMembers:" => 0x800000,
            "voiceMoveMembers:" => 0x1000000,
            "voiceUseVAD:" => 0x2000000,
            "voicePrioritySpeaker:" => 0x100
        ];

        parent::__construct($params);
    }

    function list_permissions($permcode)
    {
        foreach ($this->perms as $perm => $code)
        {
            if ($permcode & $code)
            {
                $permlist[] = $perm;
            }
        }

        return $permlist;
    }
    
    function getUserInfo()
    {
        return $this->get("$this->urlBase/users/@me", $this->userToken);
    }

    function getUserGuilds()
    {
        return $this->get("$this->urlBase/users/@me/guilds", $this->userToken);
    }

    function getGuildInfo($id)
    {
        return $this->get("$this->urlBase/guilds/$id", $this->botToken, 'Bot');
    }
}