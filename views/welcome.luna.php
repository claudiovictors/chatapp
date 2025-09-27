<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="/logo.svg" type="image/x-icon">
  <title>Welcome to Slenix</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      text-decoration: none;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: #0d0d0f;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      display: flex;
      background: #111;
      border-radius: 10px;
      overflow: hidden;
      max-width: 950px;
      width: 100%;
      box-shadow: -2px 2px 6px rgb(0 0 0 / 39%);
    }

    .left {
      flex: 1;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .left h1 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    .left p {
      margin-bottom: 25px;
      color: #aaa;
    }

    .left ul {
      list-style: none;
      margin-bottom: 25px;
    }

    .left ul li {
      margin: 10px 0;
    }

    .left ul li a {
      color: #ff2d55;
      text-decoration: none;
      font-weight: bold;
    }

    .left a:hover {
      text-decoration: underline;
    }

    .btn {
      background: #ff2d55;
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
      width: fit-content;
    }

    .btn:hover {
      background: #e62b4f;
    }

    .right {
      flex: 1;
      background: linear-gradient(135deg, #ff2d55, #ff8c00);
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .right h2 {
      font-size: 5rem;
      font-weight: bold;
      color: rgba(255, 255, 255, 0.1);
      position: absolute;
      bottom: 20px;
      right: 20px;
      -webkit-text-stroke: 1px #fff;
    }

    .logo {
      font-size: 2.8rem;
      font-weight: bold;
      color: #fff;
      text-shadow: 0 0 15px rgba(255, 45, 85, 0.8);
    }

    .svg-icon {
      width: 16px;
      height: 16px;
      vertical-align: middle;
      margin-right: 8px;
      fill: #ff2d55;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <h1>Let's get started</h1>
      <p>{{ env('APP_NAME') }} has an incredibly rich ecosystem. We suggest starting with the following.</p>
      <ul>
        <li><svg class="svg-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg> <a href="https://github.com/claudiovictors/slenix" target="_blank">Read the Documentation</a></li>
        <li><svg class="svg-icon" viewBox="0 0 24 24"><path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg> <a href="http://instagram.com/slenixphp" target="_blank">Watch video tutorials & screencasts</a></li>
      </ul>
      <a href="https://github.com/claudiovictors/slenix" target="_blank" class="btn">Deploy now</a>
    </div>
    <div class="right">
      <div class="logo">{{ env('APP_NAME') }}</div>
      <h2>{{ env('APP_VERSION') }}</h2>
    </div>
  </div>
</body>
</html>