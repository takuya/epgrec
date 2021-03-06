#!/usr/bin/env ruby

require "rubygems"
require "./model.rb"
#load constants from config.php  via dumper(config_exporter.php)
require 'json'
php_const = JSON.load `php ./config_exporter.php`
#load config.xml via nokogiri
require "nokogiri"
config_xml = Nokogiri::XML(File.open("./settings/config.xml").read)

thumbs_path  = config_xml.search("//thumbs/text()").text
ffmpeg_path  = config_xml.search("//ffmpeg/text()").text
video_path   = config_xml.search("//spool/text()").text
offset       = config_xml.search("//former_time/text()").text.to_i + 20
install_path = php_const["INSTALL_PATH"]


t1 = (Time.now-60*60*24*4).strftime('%Y-%m-%d') #一昨日
t2 = (Time.now-60*60*24*1).strftime('%Y-%m-%d') #昨日
names = Reserve.find( :all,
				      #:conditions=>" title like '%美の壺%' "
				      #:conditions=>"starttime > '2011-08-02 14:00' and starttime <  '2011-08-02 20:00'"
				      :conditions=>"starttime > '#{t1} 4:00' and starttime <  '#{t2} 4:00'"
					).map{|w| w.path}

#コマンドを作る
commands = names.map{|w| 
  "#{ffmpeg_path} -i '#{install_path}#{video_path}/#{w}' -r 1 -s 160x90 -ss #{offset} -vframes 1 -f image2 '#{install_path}#{thumbs_path}/#{w}_tmp.jpg'; "+
  "mv -f  #{install_path}#{thumbs_path}/#{w}_tmp.jpg #{install_path}#{thumbs_path}/#{w}.jpg;"
}



#commands.each{|cmd| puts  "#{cmd}" } 
commands.each{|cmd| `#{cmd}` } 





