# KnockbackSync-PM

This plugin is intended for the synchronization of knockback with the client state of the player in order to decrease inconsistencies caused by ping in PvP on Bedrock Edition.

## The Problem

PocketMine-MP operates knockback client-authoritatively – meaning that the server sends a velocity packet and the client calculates its impact. The problem is that the server does not know whether the player is currently on the ground or in the air on his screen when he takes the damage. As a result, knockback is calculated incorrectly depending on the ping of the player.

## Solution

The plugin tracks the state of each player (whether the player is currently on the ground) through the data stored in the `PlayerAuthInputPacket`. In case of damage, the plugin searches for the last value of the player's state in the buffer with a shift of `ping/2 + offset` to calculate the actual client state and apply knockback according to it.

Players on the ground receive a boost of the Y velocity (`0.4` by default), while airborne players do not have it (`0.0` by default).

## Installation

1. Download or build the `.phar` file.
2. Drop it into your server's `plugins/` folder.
3. Restart the server.

## Configuration

The config file is generated at `plugin_data/KnockbackSync/config.yml` on first run.

| Key | Default | Description |
|-----|---------|-------------|
| `ping_offset` | `25` | Extra milliseconds added to `ping/2` when looking back in the buffer. Higher values = more aggressive compensation. |
| `horizontal_kb` | `0.4` | Horizontal knockback strength. |
| `vertical_kb_ground` | `0.4` | Vertical knockback when the player was on the ground client-side. |
| `vertical_kb_air` | `0.0` | Vertical knockback when the player was airborne client-side. |
| `buffer_duration_ms` | `1000` | How many milliseconds of ground-state history to keep per player. Should be higher than the highest expected ping. |

## Limitations

- Bedrock Edition knockback is client-authoritative. A modified client can ignore the knockback packet entirely. This plugin cannot prevent that.
- Ground-state detection is an approximation based on the block below the player's reported position.
- Sprint-resetting and hit mechanics differ between Java and Bedrock. Knockback values may need tuning for your server.

## Requirements

- PocketMine-MP 5.x
- PHP 8.x

## License

[MIT](LICENSE)