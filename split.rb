#!/usr/bin/ruby
require 'pp'
$KCODE='u'
def split_ts(filename)
  cmd= " /home/takuya/.wine/drive_c/users/takuya/tssplitter/TsSplitter.exe -SD -1SEG -SEP "
  `#{cmd} "#{filename}"`
end

def swipe_pre_post_program(filename)
  dir_name = File.dirname(filename)
  name = File.basename(filename,".ts")
  name = "#{name}*HD*"
  name = File.expand_path(name,dir_name)
  Dir.glob(name).select{|e| File.size(e) < 1024*1024*120 }.each{|e|File.unlink e}
end

def move_file(filename)
  dir_name = File.dirname(filename)
  name = File.basename(filename,".ts")
  name = "#{name}*HD*"
  name = File.expand_path(name,dir_name)
  return  if Dir.glob(name).size != 1
  name = Dir.glob(name).first
  dest = name.gsub(/_HD-*[1-9]*[1-9]*/,"")
  #puts %!mv "#{name}" "#{dest}"!
  `mv -f "#{name}" "#{dest}" > /dev/null &`
end
def rm_CS_file(filename)
  dir_name = File.dirname(filename)
  name = File.basename(filename,".ts")
  name = "#{name}*CS*"
  name = File.expand_path(name,dir_name)
  Dir.glob(name).each{|e|
    FileUtils.unlink(e)
  }
end
def has_not_splitted?(name)
  begin
  ret = `ffmpeg -i "#{name}" 2>&1 | grep Program | wc -l`
  return ret.to_i > 1
  rescue => e
		  puts ret
		  puts name 
		  puts e
  end
end

def video_list()
  require 'rubygems'
  require 'active_record'
  require './model.rb'
  t1 = (Time.now-60*60*24).strftime('%Y-%m-%d') #昨日 
  t2 = (Time.now).strftime('%Y-%m-%d')          #今日
  Reserve.find(:all,:conditions=>"starttime > '#{t1} 4:00' and endtime <  '#{t2} 4:00'").map{|w|w.path}
  #Reserve.find(:all,:conditions=>"starttime > '2011-07-25 0:00' and endtime <  '2011-07-26 0:00'").map{|w|w.path}
end

class UnSplittedError < Exception; end


video_dir = "/usr/share/epgrec/video"
video_list().each{|e|
  name = "#{video_dir}/#{e}"
  #puts name
  next unless File.exists? name
  next unless has_not_splitted?   name # 処理済みはスキップ
  retry_cnt = 0;
  begin
    puts name
    split_ts(name)
    swipe_pre_post_program(name)
    move_file( name )
	rm_CS_file(name)
	raise UnSplittedError , "#{name} は処理できてませんでした" if has_splitted?(name)
   rescue UnSplittedError => e
		   retry_cnt = retry_cnt + 1
		   next if retry_cnt > 3;
		   retry
   rescue => e
     puts e,name
   end
}
##Dir::entries(video_dir).select{|e| not File.directory? e }[1,1000].each{|e|
  ##name = "#{video_dir}/#{e}"
  ##split_ts(name)
  ##swipe_pre_post_program(name)
  ##move_file( name )
##}



puts :end
