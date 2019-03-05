# schedule
A PHP script that creates a study schedule based on an API.

The script has been kept extremely simple by design.

## Functionality

The script displays one day at a time. By the default, the script shows the current day. You can navigate to the next or previous day or week, or go back to today.

The links are disabled dynamically. For example, if you are on the current day, the link "Today" is disabled. That gives you a clear indication of where you are without having to read the date.

### Limitations

The script has been written for a specific API - yours might be very different. You will most likely have to change parts of the script manually. Modifications require only a basic understanding of PHP, as the script is not very complex.

## Shortcuts

A few shortcuts are available for quick navigation.

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

| Gesture                      | Action        |
| ---------------------------- | ------------- |
| Swipe left with one finger   | Next day      |
| Swipe right with one finger  | Previous day  |
| Swipe left with two fingers  | Next week     |
| Swipe right with two fingers | Previous week |

## Command-line installation
As an alternative to downloading the files manually from GitHub, you can use the command-line.

In the desired directory, type or paste the following: `git clone https://github.com/reclaimingmytime/schedule.git`

This will place the script in the sub-folder `schedule`.

## Configuration

Note: The directory of the script and the folder "cache/" (created by the script) must be **writable** with at least chmod 700. Otherwise, the script will **fail** to do it's job.

1. Copy `config.default.php` to `config.php`.
2. Open `config.php` and change the required variables.

3. Test the page. If it does not display properly, make sure you have permissions to use the API. You might want to manually output certain variables - specifically, `$calendarJSON` . That usually tells you what the script thinks your API is.

## Command-line updates
As an alternative to downloading the new files manually, if you have installed the script through git, you can simply update your local repository using the following command:

`git fetch --all && git reset --hard origin/master`

Note that your configuration file `config.php` will be **kept** in any case, as that file is ignored by git.
