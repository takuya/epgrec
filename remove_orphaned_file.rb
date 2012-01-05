#!/usr/bin/env ruby
#準備する
require '/usr/share/epgrec/model.rb'
video_dir =  '/usr/share/epgrec/video'
#
##レコードが消えてるのに、ファイルが残ってるものを取り出して、ファイルを消す
a=Reserve.find(:all).map{|w|File.expand_path w.path, video_dir }
b = Dir.glob '/usr/share/epgrec/video/*.ts'
c = (b-a).each{|e| File.delete e  if File.file? e  }     


