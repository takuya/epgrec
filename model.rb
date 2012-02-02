#!/usr/bin/env ruby
require 'rubygems'
require 'active_record'
require 'pp'
$KCODE='u'
require "nokogiri"
config_xml = Nokogiri::XML(File.open("/usr/share/epgrec/settings/config.xml").read)
$DB_USER = config_xml.search("//db_user/text()").text
$DB_PASS = config_xml.search("//db_pass/text()").text
$DB_HOST = config_xml.search("//db_host/text()").text
$DB_NAME = config_xml.search("//db_name/text()").text
$VIDEO_PATH = "/usr/share/epgrec/video"

ActiveRecord::Base.establish_connection(
  :adapter  => "mysql",
  :host     => $DB_HOST,
  :database => $DB_NAME,
  :username => $DB_USER,
  :password => $DB_PASS,
  :encoding => "utf8"
)


#ActiveRecord::Base.establish_connection(
#  :adapter  => "sqlite3",
#  :database => "/Users/takuya/test.db"
#)

#Thread.abort_on_exception=false
class String
		def &(str)
				k = $KCODE
				$KCODE='u'
				a = self.split(//).zip(str.split(//)).map{ |e| e.uniq.size==1 }
				idx = a.index(false)
				return "" if idx == nil
				return self.split(//).first(idx).join()
				$KCODE= k
				a
		end
end


class Reserve < ActiveRecord::Base
    set_primary_key :id
    set_table_name :Recorder_reserveTbl
    set_inheritance_column :sub_type_class
  def video_fullpath
    return File.join $VIDEO_PATH, self.path
  end  
  def remove_video
    system " rm -f '#{self.video_fullpath}' &  " 
  end
  def is_ts_exists?
     File.exists? self.video_fullpath
  end
  def delete_ts_and_destroy
    return unless self.complete?
    self.remove_video 
    self.destroy
  end
  def ts_file_size
    return false unless self.is_ts_exists?
    File::stat(self.video_fullpath).size
  end
  def rec_minutes
    ((self.endtime - self.starttime)/60).to_i
  end
  def ts_file_enough_length
    ts_file_size_thresholds = 0.10 #ファイルサイズが何％以下なら録画失敗と見なすか
    ts_file_size_thresholds
    return "unknown" unless self.is_ts_exists?
    if self.ts_file_size> (self.file_size_expected * ts_file_size_thresholds) then
    	return "ok"
    else
	    return "not enough"
	  end
  end
  def find_relative_title()
		 max= Reserve.find(:all,:conditions=>"complete=true").select{|e| (e.title&self.title).size>0}.reject{|e|e.title==self.title}.map{|e| (e.title & self.title).size}.max
		 Reserve.find(:all,:conditions=>"complete=true").select{|e| (e.title&self.title).size>=max}
  end
  def file_size_expected
		  bitrate = 16 #とりあえずデフォルト
		  bitrate = 24 if self.type=="BS"
		  bitrate = 16 if self.type=="GR"
		  (bitrate * 60 * self.rec_minutes / 8)
  end
  def Reserve.delete_rec_missed
		Reserve.find(:all).map.select{|e| e.ts_file_enough_length=="not enough"}.map{|e| e.delete_ts_and_destroy }
  end
  #重複している録画を探す。
  def Reserve.find_rec_dupulicated_title
  	b = true
  	b = 1 if Reserve.connection.class.to_s.grep(/ite/) # 暫定
  	Reserve.select(" count(*), title, description ").group(:title,:description).having('count(*) > 1').where("complete = ?", b)
  end
  def Reserve.delete_duplicated_rec()
  	Reserve.delete_rec_missed
	#重複ファイルリストをとってくる
  	dup_rec_list = Reserve.find_rec_dupulicated_title
  	#削除リスト作成
  	dup_rec_list = dup_rec_list.map{|e|
  		dup_rec_entries = Reserve.where("title=? and description = ? and complete = ? " , e.title,e.description, true ).order("starttime ASC")
  		dup_rec_entries.pop #最新一件だけ残すので、削除リストから消す。
  		dup_rec_entries
  	}
  	##削除する
  	dup_rec_list = dup_rec_list.flatten
  	dup_rec_list.map{|e| e.delete_ts_and_destroy }
  end
end




