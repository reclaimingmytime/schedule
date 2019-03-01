# schedule
A basic PHP script that creates a study schedule based on an API.

The script has been kept extremely simple by design.

## Command-line installation
As an alternative to downloading the files manually from GitHub, you can use the command-line.

In the desired directory, type the following: `git clone https://github.com/reclaimingmytime/schedule.git`

## Configuration
1. Copy `config.default.php` to `config.php`.
2. Open `config.php` and change the required variables.

Note: The directory of the script and the folder "cache/" (created by the script) must be **writable** with at least chmod 700. Otherwise, the script will **fail** to do it's job.

## Usage
### Keyboard navigation

| Shortcut | Action        |
| -------- | ------------- |
| `D`      | Next Day      |
| `A`      | Previous Day  |
| `S`      | Previous Week |
| `W`      | Next Week     |
| `Enter`  | Current Week  |

Note: Arrow keys as a shortcut would interfere with keyboard-based scrolling.

### Touch navigation

| Gesture     | Action       |
| ----------- | ------------ |
| Swipe left  | Next day     |
| Swipe right | Previous day |

## Command-line updates
As an alternative to downloading the new files manually, if you have installed the script through git, you can simply update your local repository using `git pull origin master` in that directory.

Note that your configuration file `config.php` will be **kept** in any case, as that file is ignored by git.
