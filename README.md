Enuygun Opcache Clear Bundle
==================

This document contains information on how to download, install, and start
using Enuygun OpcacheClear Bundle.

1) Installing the Enuygun OpcacheClear Bundle
------------------------------------

### Use Composer

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

Add following lines into your composer.json

    [...]
    "require" : {
        [...]
        "enuyguncom/opcache-clear-bundle" : "dev-master"
    },
    "repositories" : [{
        "type" : "vcs",
        "url" : "https://github.com/EnuygunCom/opcache-clear-bundle.git"
    }],
    [...]

and then install via composer

    composer update enuyguncom/opcache-clear-bundle

Now you need to add the following configuration into config.yml file

    enuygun_com_opcache_clear:
        host_ip:    127.0.0.1:80/your-application-route
        host_name:  www.enuygun.com
        web_dir:    %kernel.root_dir%/../web
        protocol:   http
        app_version:    v1.0.0
        ip_filter: [ 127.0.0.1 ] # a list of ip addresses, may be your static local IP


Add this bundle to your application kernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new EnuygunCom\OpcacheClearBundle\EnuygunComOpcacheClearBundle(),
            // ...
        );
    }
    
    


On your deploy.rb add the following:

    before 'deploy:finalize_update', 'enuyguncom:change_version'
    after "deploy", "enuyguncom:clear_opcache"
    after "deploy:rollback", "enuyguncom:clear_opcache"
    namespace :enuygun do
      desc "Clear opcache cache"
      task :clear_opcache do
        capifony_pretty_print "--> Clear opcache cache by enuyguncom"
        run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} enuyguncom:opcache:clear --app_version=#{real_revision[-5..-1]} --host-ip=yourhost.com/your-app --env=#{symfony_env_prod}'"
        capifony_puts_ok
      end
            task :change_version, :roles => :app do
                    run "sed -i 's/\\(appVersion = \\)\\(.*'\\''\\)\\(.*\\)$/\\1'\\''#{real_revision[-5..-1]}'\\''\\3/g' #{latest_release}/web/app.php"
                    capifony_pretty_print "--> Version updated"    
            end
    end