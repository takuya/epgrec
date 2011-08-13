#!/usr/bin/env ruby
require 'rubygems'
require 'active_record'



require "nokogiri"
config_xml = Nokogiri::XML(File.open("./settings/config.xml").read)
$DB_USER = config_xml.search("//db_user/text()").text
$DB_PASS = config_xml.search("//db_pass/text()").text
$DB_HOST = config_xml.search("//db_host/text()").text
$DB_NAME = config_xml.search("//db_name/text()").text
  


ActiveRecord::Base.establish_connection(
  :adapter  => "mysql",
  :host     => $DB_HOST,
  :database => $DB_NAME,
  :username => $DB_USER,
  :password => $DB_PASS,
  :encoding => "utf8"
)



class Reserve < ActiveRecord::Base
    set_primary_key :id
    set_table_name :Recorder_reserveTbl
    set_inheritance_column :sub_type_class
end




