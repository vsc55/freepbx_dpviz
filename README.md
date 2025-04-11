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
### Upgrading from a Previous Version
If upgrading, uninstall the current version before proceeding.

#### Uninstall via Command Line:
```sh
fwconsole ma uninstall dpviz
```

#### Install via Command Line:
```sh
fwconsole ma downloadinstall https://github.com/madgen78/dpviz/archive/refs/heads/main.zip
```

### Install via FreePBX Admin Panel
1. **Log into FreePBX**, then navigate to **Admin > Module Admin**.
2. Click **Upload Modules**.
3. **Download the module** from the following link: [Download dpviz](https://github.com/madgen78/dpviz/archive/refs/heads/main.zip).
4. Set the upload type to **"Upload (From Hard Disk)"**.
5. Click **Choose File**, select the downloaded module, then click **Upload (From Hard Disk)**.
6. After the upload completes, click **Local Module Administration**.
7. Scroll down to **Dial Plan Vizualizer** under the **Reports** section and click on it to expand.
8. Click **Install** and then click **Process** (at the bottom of page) to complete the installation.

## Usage
1. **Log in to your PBX** and navigate to **Reports > Dial Plan Vizualizer**.
2. **Select or search for an inbound route** using the side menu.
3. **Labels** are placed on the right (vertical) or above (horizontal) the paths drawn.

### Highlighting Call Paths
- Click **Highlight Paths**, then select a node or edge to highlight it (links are inactive).
- To clear highlights, click **Remove Highlights**.

### Navigation
- **Redraw from a Node:** Press Ctrl (Cmd on macOS) and left-click a node to make it the new starting point in the diagram. To revert, Ctrl/Cmd + left-click the parent node.
- **Pan** by holding down the left mouse button and dragging.
- **Zoom** using the mouse wheel.

### Additional Features
- **Hover** over a path to highlight the path between destinations.
- **Click** on a destination to open it in a new tab.
- **Click** on a "Match: (timegroup)" or "NoMatch" to open it in a new tab.
- To export, click **"Export as .png"**.

### Dependencies
- **PHP >= 5.4.0**

## License
This module's code is licensed under [GPLv3+](http://www.gnu.org/licenses/gpl-3.0.txt).

[__Buy me a coffee! :coffee:__](https://buymeacoffee.com/adamvolchko)

