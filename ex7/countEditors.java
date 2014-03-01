import java.io.IOException;
import java.util.StringTokenizer;
import java.util.ArrayList;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;
import org.apache.hadoop.util.GenericOptionsParser;

public class countEditors {

  public static class EditorMapper 
       extends Mapper<Object, Text, Text, Text>{
    
    private Text editor = new Text();	
    private Text article = new Text();
      
    public void map(Object key, Text value, Context context
                    ) throws IOException, InterruptedException {
      StringTokenizer itr = new StringTokenizer(value.toString());
      int count = 0;

      while (itr.hasMoreTokens()) {
	   if (++count == 4) {
		article.set(itr.nextToken());
	   }
	   else if (count == 6) {
      		editor.set(itr.nextToken());
      		context.write(article, editor);
		break;
	   }
	   else 
      		itr.nextToken();
      }
    }
  }
  
  public static class EditorReducer 
       extends Reducer<Text,Text,Text,IntWritable> {

    private IntWritable result = new IntWritable();
    private ArrayList<String> arr = new ArrayList<String>();

    public void reduce(Text key, Iterable<Text> values, 
                       Context context
                       ) throws IOException, InterruptedException {

      arr.clear();
      for (Text val : values) {
	if(!arr.contains(val.toString())){ 
            arr.add(val.toString());
	}
      }
      result.set(arr.size());
      context.write(key, result);
    }
  }

  public static void main(String[] args) throws Exception {
    Configuration conf = new Configuration();
    String[] otherArgs = new GenericOptionsParser(conf, args).getRemainingArgs();
    if (otherArgs.length != 2) {
      System.err.println("Usage: countEditors <in> <out>");
      System.exit(2);
    }
    Job job = new Job(conf, "Wiki editor counts");
    job.setJarByClass(countEditors.class);
    job.setMapperClass(EditorMapper.class);
    job.setReducerClass(EditorReducer.class);
    job.setMapOutputKeyClass(Text.class);
    job.setMapOutputValueClass(Text.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(IntWritable.class);
    job.setNumReduceTasks(1);
    FileInputFormat.addInputPath(job, new Path(otherArgs[0]));
    FileOutputFormat.setOutputPath(job, new Path(otherArgs[1]));
    System.exit(job.waitForCompletion(true) ? 0 : 1);
  }
}
