#!/usr/bin/env ruby
require 'rubygems'
require 'active_record'


ActiveRecord::Base.establish_connection(
  :adapter=>"mysql",
  :host  =>"localhost",
  :database =>"epgrec",
  :username=>"root",
  :password=>"***",
  :encoding=>"utf8"
)



class Reserve < ActiveRecord::Base
    set_primary_key :id
    set_table_name :Recorder_reserveTbl
    set_inheritance_column :sub_type_class
end




