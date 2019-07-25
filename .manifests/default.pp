
############################################
# Install packages
############################################

package { "apache2":
    ensure => present 
}

package { "php":
    ensure => present
}

package { "libapache2-mod-php":
    ensure => present
}

package { "php-curl":
    notify => Service["apache2"],
    ensure => present
}

package { "php-gd":
    notify => Service["apache2"],
    ensure => present
}

package { "php-mbstring":
    notify => Service["apache2"],
    ensure => present
}

package { "php-mysql":
    notify => Service["apache2"],
    ensure => present
}

package { "php-xml":
    notify => Service["apache2"],
    ensure => present
}

package { "composer":
    ensure => present
}

package { "mysql-server":
    ensure => present,
}

package { "git":
    ensure => present
}

############################################
# Start Services
############################################

service { "apache2":
    ensure => "running",
    require => Package["apache2"]
}

service { "mysql":
    ensure => "running",
    require => Package["mysql-server"]
}

############################################
# Database initalization
# * Enable root MySQL user
# * Pre-create empty databases
############################################

exec { "sql-passwd":
    command => "echo \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

exec { "core_op":
    command => "echo \"CREATE DATABASE IF NOT EXISTS core_op;\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

exec { "core_trans":
    command => "echo \"CREATE DATABASE IF NOT EXISTS core_trans;\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

exec { "trans_archive":
    command => "echo \"CREATE DATABASE IF NOT EXISTS trans_archive;\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

exec { "opdata":
    command => "echo \"CREATE DATABASE IF NOT EXISTS opdata;\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

exec { "translog":
    command => "echo \"CREATE DATABASE IF NOT EXISTS translog;\" | sudo mysql",
    path => "/bin:/usr/bin",
    require => Service["mysql"]
}

############################################
# Install CORE via git
############################################

vcsrepo { "/var/www/html/IS4C":
    ensure => present,
    provider => git,
    source => "https://github.com/CORE-POS/IS4C.git",
    depth => 1,
    require => Package["git"]
}

############################################
# Install composer packages
############################################

exec { "/usr/bin/composer install":
    cwd => "/var/www/html/IS4C",
    onlyif => "/usr/bin/test -e /var/www/html/IS4C",
    environment => "COMPOSER_HOME=/home/vagrant/.composer"
}

############################################
# Add config & log files
# * configs are barebones
# * logs are empty files w/ proper permissions
############################################

file { "/var/www/html/IS4C/fannie/config.php":
    owner => "www-data",
    content => file("/vagrant/.manifests/config.php")
}

file { "/var/www/html/IS4C/fannie/logs/fannie.log":
    owner => "www-data",
    ensure => "present"
}

file { "/var/www/html/IS4C/pos/is4c-nf/ini.json":
    owner => "www-data",
    content => file("/vagrant/.manifests/ini.json")
}

file { "/var/www/html/IS4C/pos/is4c-nf/log/lane.log":
    owner => "www-data",
    ensure => "present"
}

file { "/var/www/html/IS4C/pos/is4c-nf/log/debug_lane.log":
    owner => "www-data",
    ensure => "present"
}

############################################
# Run built-in installers
############################################

exec { "/usr/bin/wget http://localhost/IS4C/fannie/install/":
    onlyif => "/usr/bin/test -e /var/www/html/IS4C",
}

exec { "/usr/bin/wget http://localhost/IS4C/pos/is4c-nf/install/":
    onlyif => "/usr/bin/test -e /var/www/html/IS4C",
}

