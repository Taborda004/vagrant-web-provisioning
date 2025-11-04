Vagrant.configure("2") do |config|
  config.vm.box_check_update = false
  config.vm.define "web" do |web|
    # Box base Ubuntu 20.04
    web.vm.box = "generic/ubuntu2004"
    web.vm.hostname = "web"
    web.vm.network "private_network", ip: "192.168.122.10"
    web.vm.synced_folder "./www", "/vagrant/www", type: "rsync", create: true
    web.vm.provision "shell", path: "provision-web.sh"
    web.vm.provider :libvirt do |v|
      v.cpus = 1
      v.memory = 1024
    end
  end

  config.vm.define "db" do |db|
    db.vm.box = "generic/ubuntu2004"
    db.vm.hostname = "db"
    db.vm.network "private_network", ip: "192.168.122.11"
    db.vm.synced_folder ".", "/vagrant", type: "rsync", rsync__exclude: [".git/", "www/"]
    db.vm.provision "shell", path: "provision-db.sh"
    db.vm.provider :libvirt do |v|
      v.cpus = 1
      v.memory = 1024
    end
  end
end