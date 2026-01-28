local QBCore = nil
local ESX = nil

CreateThread(function()
    if Config.framework == "qbcore" or Config.framework == "qbox" then
        QBCore = exports['qb-core']:GetCoreObject()
    elseif Config.framework == "esx" then
        TriggerEvent('esx:getSharedObject', function(obj) ESX = obj end)
    end
end)

local function getCitizenId(src)
    if Config.framework == "qbcore" or Config.framework == "qbox" then
        local Player = QBCore and QBCore.Functions.GetPlayer(src) or nil
        if Player and Player.PlayerData and Player.PlayerData.citizenid then
            return Player.PlayerData.citizenid
        end
    elseif Config.framework == "esx" then
        local xPlayer = ESX and ESX.GetPlayerFromId(src) or nil
        if xPlayer and xPlayer.identifier then
            return xPlayer.identifier
        end
    end
    return nil
end

local function getLicense(src)
    for _, id in ipairs(GetPlayerIdentifiers(src)) do
        if string.sub(id, 1, 8) == "license:" then
            return id
        end
    end
    return nil
end

local function getDiscord(src)
    for _, id in ipairs(GetPlayerIdentifiers(src)) do
        if string.sub(id, 1, 8) == "discord:" then
            return id
        end
    end
    return nil
end

local function sendLog(src, logType, message, meta)
    local payload = {
        player = {
            name = GetPlayerName(src),
            license = getLicense(src),
            discord = getDiscord(src),
            citizenid = getCitizenId(src),
            server_id = src
        },
        log = {
            type = logType,
            message = message,
            meta = meta or {}
        }
    }

    PerformHttpRequest(Config.panelUrl, function(status, body, headers)
    end, "POST", json.encode(payload), {
        ["Content-Type"] = "application/json",
        ["X-Api-Key"] = Config.apiKey
    })
end

AddEventHandler("playerConnecting", function(name, setKickReason, deferrals)
    deferrals.defer()
    deferrals.update("Kimlik dogrulaniyor...")

    local src = source
    local payload = json.encode({
        license = getLicense(src),
        discord = getDiscord(src),
        citizenid = getCitizenId(src)
    })

    PerformHttpRequest(Config.panelBase .. "/api/ban-check", function(status, body)
        if status ~= 200 then
            deferrals.done()
            return
        end
        local data = json.decode(body)
        if data and data.banned then
            deferrals.done("Banned: " .. (data.reason or ""))
            return
        end
        deferrals.done()
    end, "POST", payload, {
        ["Content-Type"] = "application/json",
        ["X-Api-Key"] = Config.apiKey
    })
end)

AddEventHandler("playerJoining", function()
    local src = source
    sendLog(src, "join", "Player Joined")
end)

AddEventHandler("playerDropped", function(reason)
    local src = source
    sendLog(src, "leave", "Player Leaved", { reason = reason })
end)

CreateThread(function()
    while true do
        Wait(10000)
        for _, src in ipairs(GetPlayers()) do
            local payload = json.encode({ server_id = tonumber(src) })
            PerformHttpRequest(Config.panelBase .. "/api/actions/pull", function(status, body)
                if status ~= 200 then return end
                local data = json.decode(body)
                if not data or not data.actions then return end
                for _, action in ipairs(data.actions) do
                    if action.action == "kick" then
                        DropPlayer(tonumber(src), action.reason or "Kick")
                    elseif action.action == "ban" then
                        DropPlayer(tonumber(src), action.reason or "Ban")
                    elseif action.action == "unban" then
                    end
                    local donePayload = json.encode({ action_id = action.id, status = "done" })
                    PerformHttpRequest(Config.panelBase .. "/api/actions/complete", function() end, "POST", donePayload, {
                        ["Content-Type"] = "application/json",
                        ["X-Api-Key"] = Config.apiKey
                    })
                end
            end, "POST", payload, {
                ["Content-Type"] = "application/json",
                ["X-Api-Key"] = Config.apiKey
            })
        end
    end
end)

