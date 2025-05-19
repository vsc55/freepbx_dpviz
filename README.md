# Dial Plan Vizualizer (dpviz)

## Overview
The **Dial Plan Vizualizer** (dpviz) is a module for [FreePBX®](http://www.freepbx.org/), an open-source graphical user interface for managing [Asterisk](http://www.asterisk.org/) phone systems. FreePBX is licensed under GPL.

This module visually maps out the call flow for any inbound route, making it an essential tool for PBX administrators. It simplifies troubleshooting, optimization, and documentation of call routing by providing a clear, interactive diagram of how calls are handled.

It is particularly useful for:
- **Understanding call distribution** – "Which extensions ring when someone calls X?"
- **Tracing call logic** – "When a call comes in on Y, does it go directly to the IVR, or are Time Conditions applied first?"
- **Identifying misconfigurations** – Quickly spot and correct unintended call routing behaviors.
- **Streamlining PBX management** – Reduce the time spent manually tracking call flows in complex systems.

## Installation

#### Install or Upgrade via Command Line:
```sh
fwconsole ma downloadinstall https://github.com/madgen78/dpviz/archive/refs/heads/main.zip
```

### Install or Upgrade via FreePBX Admin Panel
1. **Log into FreePBX**, then navigate to **Admin > Module Admin**.
2. Click **Upload Modules**.
3. **Download (From Web)** Enter ```https://github.com/madgen78/dpviz/archive/refs/heads/main.zip``` then click **Download (From Web)**.
- OR
3. **Download the module** from the following link: [Download dpviz](https://github.com/madgen78/dpviz/archive/refs/heads/main.zip).
    - Set the type to **"Upload (From Hard Disk)"**.
    - Click **Choose File**, select the downloaded module, then click **Upload (From Hard Disk)**.
4. After the download or upload completes, click **Local Module Administration**.
5. Scroll down to **Dial Plan Vizualizer** under the **Reports** section and click on it to expand.
6. Click **Install** or **Upgrade to -version- and Enable** and then click **Process** (at the bottom of page) to complete the installation.

## Usage
1. **Log in to your PBX** and navigate to **Reports > Dial Plan Vizualizer**.
2. **Select or search for an Inbound Route, Time Condition, Call Flow, IVR, Queue, Ring Group, Dynamic Route, Announcement, Language, or Misc Application** using the dropdown menu.
3. **Labels** are placed on the right (vertical) or above (horizontal) the paths drawn.

### Highlighting Call Paths
- Click **Highlight Paths**, then select a node or edge to highlight it (links are inactive).
- To clear highlights, click **Remove Highlights**.

### Navigation
- **Redraw from a Node:** Press Ctrl (Cmd on macOS) and left-click a node to make it the new starting point in the diagram. To revert, Ctrl/Cmd + left-click the "Back" node.
- **Pan** by holding down the left mouse button and dragging.
- **Zoom** using the mouse wheel.

### Additional Features
- **Listen** to recordings assigned to Announcement, Dynamic Route, IVR, and Play Recording modules. (**Note**: Supports multi-part and multi-language recordings. Only .wav files are supported.)
- **Hover** over a path to highlight the path between destinations.
- **Click** on a destination to open it in a new tab.
- **Click** on a "Match: (timegroup)" or "NoMatch" to open it in a new tab.
- **Export** the dial plan with standard or custom filename. Choose between high and standard quality. 

### Dependencies
- **FreePBX >= 14.0**
- **PHP >= 5.4.0**

## License
This module's code is licensed under [GPLv3+](http://www.gnu.org/licenses/gpl-3.0.txt).

[__Buy me a coffee! :coffee:__](https://buymeacoffee.com/adamvolchko)

