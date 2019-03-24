# schedule
A PHP script that creates a study schedule based on an API.

The script has been kept simple by design.

## Functionality

The script displays one day at a time. By the default, the script shows the current day. You can navigate to the next or previous day or week, or go back to today.

The links are disabled dynamically. For example, if you are on the current day, the link "Today" is disabled. That gives you a clear indication of where you are without having to read the date.

### Input validation

The date is checked for validity, so `February 30` and `November 31` will be rejected. 

Custom classes are matched against a previous set of allowed classes. So, if `Class-1` and `Class-2` are allowed, `Class-10` will be rejected.

#### Rejected Input

When input is rejected, the default value is used without any message.

### Limitations

**Note**: The script has been written for a specific API - yours might be very different. You will most likely have to change parts of the script manually. Modifications require only a basic understanding of PHP, as the script is not very complex.

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

## Installation
As an alternative to downloading the files manually from GitHub, you can use the command-line.

In the desired directory, type or paste the following: `git clone https://github.com/reclaimingmytime/schedule.git`

This will place the script in the sub-folder `schedule`.

## Updating

As an alternative to downloading the new files manually, if you have installed the script through git, you can simply update your local repository using the following command:

`git pull origin master`

Note that your configuration file `config.php` will be **kept** in any case, as that file is ignored by git.

## Configuration

### Instructions

Note: The directory of the script and the folder "cache/" (created by the script) must be **writable** with at least chmod 700. Otherwise, the script will **fail** to do it's job.

1. Copy `config.default.php` to `config.php`.
2. Open `config.php` and change the required variables.
3. Test the page. If it does not display properly, make sure you have permissions to use the API. You might want to manually output certain variables - specifically, `$calendarJSON` . That usually tells you what the script thinks your API is.

### Variables and Constants in config.php

#### Time
| Variable           | Description                                                  |
| ------------------ | ------------------------------------------------------------ |
| `$timezone`        | *Optional*. The timezone that should be used for displaying the event time. *Default: Server setting.* |
| `$minDate`         | *Optional*. All dates below the minimum date are considered invalid. Useful if your API only serves the current year, e.g. `"01.01." . date("Y")` *Default: 1.1.1970 (unix time)* |
| `$excludeWeekends` | *Optional*. Weekends will be skipped with an appropriate notice. *Default: false.* |
#### API Connection

| Variable          | Description                                                  |
| ----------------- | ------------------------------------------------------------ |
| `$defaultClass`   | **Required**. Default class when fetching the API.           |
| `$allowedClasses` | *Optional*. Array of allowed classes. If undefined or empty, only $defaultClass is allowed and the class switcher is hidden. |
| `$api`            | **Required**. URL of the API. Must be and accessible URL. Assumes the API serves JSON. |

#### Handling Data

| Variable or Constant                         | Description                                                  |
| -------------------------------------------- | ------------------------------------------------------------ |
| `CALENDAR`                                   | *Optional.* The array index of the calendar events.          |
| `START`, `END`, `SUBJECT`, `ROOM` and `PROF` | **Required**. Array index for the respective data. START and END are used for time and date and assume the following format: *YYYY-MM-DD HH:MM:SS*. |
| `$emptyProfs`                                | *Optional.* Array of names that should be considered empty, such as "-". |
| `$subjects`                                  | *Optional.* Associative array of preferred names for subjects, e.g. "Break" instead of "Recess" |
| `$profs`                                     | *Optional.* Associative array of initials to full names.     |
| `$roomPrefix`                                | *Optional.* A prefix that might seem redundant, such as "Room-". |
| `$rooms`                                     | *Optional.* Associative array of rooms to custom names.      |

### Included Files

In the "include" folder, you will find several files:

| File                    | Description                                                  |
| ----------------------- | ------------------------------------------------------------ |
| `globals.php`           | Multi-purpose functions that make your life easier, such as `escape($string)` instead of `htmlspecialchars($string, ENT_QUOTES)` |
| `data-acquisition.php`  | Functions for fetching the data from the API. This includes retrieving and validating a custom class, if specified. |
| `data-processing.php`   | Processing date, time and event data. This includes removing invalid data, where specified in the `config.php`. |
| `data-presentation.php` | The logic for displaying the events. This mostly includes the necessary markup. |