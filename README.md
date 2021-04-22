# Powerpress Auth Redirect
### A small plugin for redirecting bad logins to [Blubrry PowerPress](http://wordpress.org/plugins/powerpress/ "Blubrry PowerPress") premium podcast feeds

Powerpress, like most podcast feed software, protects its premium member feeds using HTTP authentication. If a member tries to access the feed, they receive a browser prompt to type their username and password. If their login is incorrect, they're given the login prompt again.

Unfortunately most Podcast apps do not understand HTTP authentication, or don't implement it properly. When the server asks for a login prompt (via a 401 HTTP error), the Podcast app simply tries the request again. This can lead to misconfigured Podcast apps DDoSing Podcaster's Wordpress sites.

**Powerpress Auth Redirect** resolves this issue with a simple change. Instead of returning an error if a feed's login is incorrect, users are instead redirected to another feed you create for handling errors. On this feed you can publish an episode explaining that they need to setup their Podcast app correctly, or move to another app.

Relatedly, I strongly encourage everyone to check out [The British History Podcast](https://www.thebritishhistorypodcast.com/ "The British History Podcast"), an excellent podcast covering British history from the stone age, through the Celts, Anglo-Saxons, Normans, and beyond.

## Setup

1. In Powerpress, create a new, public, podcast feed. Likely you want to name it "error", but whatever name will work. You should also publish an episode on this feed explaining that they need to setup their Podcast app correctly.

1. Upload this plugin, either into the wordpress wp-content/plugins directory, or as a zip file in the Wordpress interface.

1. In Wordpress's plugin editor, select this plugin, then update the `POWERPRESS_AUTH_REDIRECT_URL` to the full URL of your error feed.

1. (*Optional*) Normally this plugin will only redirect bad logins. If no user/pass is provided at all, the feed will still attempt to prompt for a login. If the Podcast app does not support logins at all, it could still DDoS your server by continuously retrying. If you want to redirect requests for certain apps or IPs entirely if they don't provide a login, you can edit these two settings.

    a.  `POWERPRESS_AUTH_REDIRECT_USERAGENTS` allows you to specify useragents to always redirect if they don't provide a working login. The examples given are for the Apple Podcast app.
	
    b. `POWERPRESS_AUTH_REDIRECT_IPS` allows you to specify IP addresses to always redirect if they don't provide a working login.
  
1. Activate this plugin in Wordpress. 

1. **Test your premium feeds**. Recommend doing so in cognito / private browsing mode, as once your browser receives a response besides a 401 error, it will not attempt a login prompt again until you close and open it.
  a. If you go to one in your browser, it should still show a login prompt.
  b. If you give a working login, you should receive your normal members feed
  c. If you give a bad login, you should be redirected to your error feed.
  d. If you add your user agent to `POWERPRESS_AUTH_REDIRECT_USERAGENTS`, or your IP address to `POWERPRESS_AUTH_REDIRECT_IPS`, you should receive no login prompt at all, going straight to the error feed.
  
  
## TODO

Currently configuring this plugin requires editing the plugin php file itself. I'll come back to this to provide configuration using the Wordpress admin.