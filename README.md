# byTorsten.React
Provides seamless react integration in [neos/flow](https://flow.neos.io/). 

## Dependencies:
This package relies an a binary file that is our rendering engine. 
Currently there are 2 supported enviroments:

[MacOS Renderer](https://github.com/bytorsten/byTorsten.React.macOSRenderer) and [Linux Renderer](https://github.com/bytorsten/byTorsten.React.LinuxRenderer)

## Usage:
TL;DR: Use the [kickstarter package](https://github.com/bytorsten/byTorsten.React.Kickstarter) to get you up an running in no time


### Views.yaml
Tell flow that it has to use the ReactView for rendering your controller actions:
```
-
  requestFilter: 'isPackage("Your.Package:Name")'
  viewObjectName: 'byTorsten\React\Core\View\ReactView'
```
optional set the server and client file pattern (more on that later, below are the default values):
```
  options:
    reactClientFilePattern: 'resource://Your.Package:Name/Private/React/index.client.js'
    reactServerFilePattern: 'resource://Your.Package:Name/Private/React/index.server.js'
```

### Creating the js environment
By default, the react components live in `Resources/Private/React`. To follow some best practices, initialize a new js package in that directory:
```
yarn init
```
The you should add `react` and `react-dom` as an dependency:
```
yarn add react react-dom
```
*actually the render engine has react and react-dom included as a fallback, but using your own gives you the certainty that the expected version is used*

Now you can create your components as usual (ideally in a subfolder e.g. `src`).

### Creating the server rendering entry point (index.server.js)
This file determines how the rendering engine renders your bundle on the server (SSR!) 
The server entry point has to export a function. This functions is hold in memory by the rendering engine to make successive renderings faster.
