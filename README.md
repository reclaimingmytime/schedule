# schedule
A basic PHP script that creates a study schedule based on an API.

The script has been kept extremely simple by design.

## Command-line installation
As an alternative to downloading the files manually from GitHub, you can use the command-line.

In the desired directory, type the following: `git clone https://github.com/reclaimingmytime/schedule.git`

## Configuration
1. Copy `config.default.php` to `config.php`.
2. Open `config.php` and change the required variables.

Note: The directory must be writable (at least chmod 700). Otherwise, the API cache file will not be writable, which is required for the script to work.

## Usage
### Keyboard navigation

| Shortcut     | Action        |
| ------------ | ------------- |
| `D` or `L`   | Next Day      |
| `A` or `H`   | Previous Day  |
| `S` or `F`   | Previous Week |
| `W` or `K`   | Next Week     |
| `Enter`      | Current Week  |

Note: Arrow keys are not used in order to not to interfere with any keyboard-based scrolling.

### Touch navigation

| Gesture     | Action       |
| ----------- | ------------ |
| Swipe left  | Next day     |
| Swipe right | Previous day |

## Command-line updates
As an alternative to downloading the new files manually, if you have installed the script through git, you can simply update your local repository using `git pull rebase origin` in that directory.

Note that your configuration file `config.php` will be **kept** in any case, as that file is ignored by git.
